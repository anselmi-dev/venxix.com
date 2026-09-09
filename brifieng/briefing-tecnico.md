# Cuotas — Briefing técnico de desarrollo

## 1. Resumen

Cuotas es una app para organizar tarjetas y cuentas, ver cuánto se debe por cuota, proyectar pagos futuros en UYU/USD y recordar vencimientos. Ya existe el diseño completo (landing, auth, onboarding, panel, resumen de cuotas, perfil, suscripción/facturación, pricing, páginas de error y legales) en HTML/CSS estático. Este documento define cómo llevarlo a producción con el stack elegido.

## 2. Arquitectura

```
                        Laravel
                           │
              ┌────────────┴────────────┐
              │                         │
         Aplicación                Administración
      (usuario final)             (equipo interno)
              │                         │
          Inertia                   Filament
              │                         │
          Vue + TS                  Livewire
              │                         │
              └──────── Laravel ────────┘
```

Un mismo backend Laravel sirve dos frontends distintos:

- **Aplicación** (`/`, `/panel`, `/perfil`, etc.): Inertia + Vue 3 + TypeScript. Es lo que usa la persona que organiza sus cuotas.
- **Administración** (`/admin`): Filament, para el equipo — soporte, ver usuarios, planes activos, MRR, cancelaciones. No se expone a usuarios finales.

Piezas de infraestructura:

| Pieza | Para qué |
|---|---|
| **PostgreSQL** | Base de datos principal. Buen soporte de tipos numéricos exactos (`numeric`) para montos — no usar `float` para dinero. |
| **Redis** | Cache, sesiones, colas (detrás de Horizon) y broadcasting (detrás de Reverb). |
| **Horizon** | Dashboard y gestión de colas: envío de recordatorios, generación de facturas, importación de estados de cuenta, webhooks de pago. |
| **Reverb** | WebSockets propios (sin depender de Pusher). Actualiza el panel en tiempo real y empuja notificaciones de vencimiento. |

## 3. Modelo de datos (borrador)

- `users` — datos de cuenta, moneda preferida, flags de onboarding completado.
- `cards` — tarjetas/cuentas del usuario (banco, alias, últimos 4 dígitos, tipo, moneda).
- `purchases` — una compra en cuotas (comercio, monto total, cantidad de cuotas, moneda de origen).
- `installments` — cada cuota individual de una `purchase` (número, monto, fecha de vencimiento, estado: pendiente/pagada).
- `notes` — notas libres asociadas al usuario o a una tarjeta.
- `plans` — Gratis / Pro / Familiar, con sus límites y precio.
- `subscriptions`, `plan_features`, `feature_usages` — gestionadas por **Subscriptionify** (agnóstico de gateway, ver sección 5).
- `notifications` — tabla nativa de Laravel (`Illuminate\Notifications\DatabaseNotification`): `id`, `type`, `notifiable_type/id`, `data` (JSON), `read_at`. No se crea a mano, la genera el propio framework.
- `exchange_rates` — cotización diaria UYU/USD, si se decide traerla de una API en vez de que el usuario la cargue a mano.

`installments` es la tabla que alimenta el resumen mensual y el calendario que ya diseñamos — vale la pena indexarla por `(user_id, due_date)`.

## 4. Mapeo de vistas ya diseñadas → páginas Inertia

| Vista estática | Ruta | Componente Vue | Notas |
|---|---|---|---|
| `landing.html` | `/` | `Pages/Landing.vue` | Pública. |
| `login.html` / `register.html` | `/login`, `/register` | `Pages/Auth/*.vue` | Laravel Fortify o Breeze (headless) + Inertia. |
| `recuperar.html` → `nueva-clave.html` | `/forgot-password`, `/reset-password/{token}` | `Pages/Auth/*.vue` | Flujo estándar de Fortify. |
| `onboarding-1/2/3.html` | `/onboarding/*` | `Pages/Onboarding/*.vue` | Middleware que redirige acá si `onboarding_completed_at` es null. |
| `tarjetas.html` | `/panel` | `Pages/Dashboard.vue` | Calendario y notas como componentes propios; datos vía props de Inertia, no fetch aparte. |
| `cuotas.html` | `/tarjetas/{card}` | `Pages/Cards/Show.vue` | El gráfico de barras y los meses se calculan en el backend (`InstallmentProjectionService`) y se pasan ya listos. |
| `perfil.html` | `/perfil` | `Pages/Profile/*.vue` | Las 4 secciones (datos, contraseña, moneda, suscripción) pueden ser un solo componente con anclas, como en el HTML. |
| `facturacion.html` | `/facturacion` | `Pages/Billing.vue` | Alimentado por el registro de pagos que exponga el gateway conectado a Subscriptionify. |
| `pricing.html` | `/precios` | `Pages/Pricing.vue` | Pública. |
| `terminos.html` / `privacidad.html` | `/terminos`, `/privacidad` | `Pages/Legal/*.vue` | Contenido puede vivir en Markdown y renderizarse, para no tocar código cada vez que cambia el texto legal. |
| `notificaciones.html` + campana | `/notificaciones` | `Pages/Notifications/Index.vue` + `Components/NotificationBell.vue` | Ver sección 6. |
| `404` / `403` / `500` | — | `Pages/Errors/*.vue` | Se registran en el `HandleInertiaRequests` / exception handler de Laravel. |

Recomendación: llevar `styles.css` a Tailwind config (colores `--bg`, `--ink`, `--gold`, radios, etc. como tokens del `tailwind.config.ts`), en vez de mantener un CSS aparte. Así los componentes Vue usan clases utilitarias consistentes con el diseño ya definido.

## 5. Suscripciones y pagos — Subscriptionify

Se eligió **Subscriptionify** en vez de Cashier porque el requisito es poder agregar cualquier método de pago sin quedar atado a Stripe. Subscriptionify modela planes, features y uso en tus propias tablas, y deja el cobro en sí como una pieza intercambiable:

- Define `plans` (Gratis, Pro, Familiar) y sus `features` (cantidad de tarjetas, calendario, exportar, etc.) en tu base de datos, no en el dashboard de un proveedor.
- El gate de funciones se resuelve contra Subscriptionify (`$user->subscribedTo('pro')` o el equivalente según su API), no contra el gateway.
- **El cobro real queda afuera del paquete**, a propósito: se implementa una clase de gateway propia por proveedor (Stripe, MercadoPago, el que se sume después), todas cumpliendo la misma interfaz. Es el mismo patrón que usa `aiarmada/cashier`: un contrato común (`checkout()`, `cancel()`, `handleWebhook()`) y una clase concreta por proveedor.
- Flujo sugerido:
  1. Usuario elige plan en `pricing.html` → se crea la `subscription` en estado `pending` en Subscriptionify.
  2. Se invoca la clase de gateway seleccionada (`StripeGateway` o `MercadoPagoGateway`) para el checkout real.
  3. El webhook del proveedor (job en cola, Horizon) confirma el pago y activa la `subscription` en Subscriptionify.
  4. Cancelar desde el perfil llama al gateway correspondiente y actualiza el estado en Subscriptionify — la UI (`perfil.html` / `facturacion.html`) no necesita saber contra qué proveedor se cobró.
- Ventaja concreta para vos: agregar un método de pago nuevo (ej. un wallet local uruguayo) es escribir una clase de gateway más, no migrar todo el sistema de suscripciones.

## 6. Notificaciones — sistema nativo + campana propia

Se descartó un paquete de terceros para esto: el canal `database` de Laravel ya crea la tabla `notifications` con `read_at` nullable, que es exactamente historial + estado visto/no visto. Se construye la UI (campana + listado) a medida, ya diseñada sobre el sistema visual (ver `notificaciones.html` y el componente de campana agregados al `topnav`).

- **Backend**: cada evento relevante (vencimiento próximo, pago fallido, suscripción renovada, bienvenida) dispara una `Notification` de Laravel con canal `database` (y `mail` cuando corresponda). `$user->unreadNotifications->count()` alimenta el badge de la campana.
- **Tiempo real**: se agrega el canal `broadcast` sobre Reverb para que el badge y el dropdown se actualicen sin recargar — Inertia + un listener de Echo en `Components/NotificationBell.vue`.
- **Frontend**:
  - `Components/NotificationBell.vue` — el ícono en el `topnav`, con el punto de "no leído" y el dropdown de las últimas notificaciones (mapea `notificaciones.html`).
  - `Pages/Notifications/Index.vue` — listado completo, agrupado por fecha, con filtro Todas/No leídas y "Marcar todas como leídas".
  - Acción de marcar como leída: `PATCH /notificaciones/{id}/leer` (o "marcar todas") — vía Inertia, sin salir de la página.
- Esto además simplifica el punto 6 anterior (recordatorios de vencimiento): el job programado que ya estaba previsto solo necesita disparar `$user->notify(new InstallmentDueSoon($installment))` en vez de armar su propia tabla.

## 7. Importación de estados de cuenta

Es el paso que más fricción quita (evita cargar cuota por cuota a mano):

- Job en cola que recibe un CSV/PDF, lo parsea (regex o librería según el banco) y crea `purchases`/`installments` en borrador para que el usuario confirme antes de guardar.
- Empezar solo con CSV; PDF con OCR es una iteración posterior.

## 8. Panel de administración (Filament)

Resources sugeridos:

- **Users** — ver, buscar, impersonar para soporte.
- **Subscriptions** — plan, estado, MRR agregado (widget).
- **Cards / Installments** — solo lectura, para soporte cuando alguien reporta un cálculo raro.
- **Plans** — editar precios y límites sin deploy.
- Widget de dashboard: altas del mes, cancelaciones, MRR.

## 9. Fases sugeridas

1. **MVP**: auth, onboarding, alta manual de tarjetas y cuotas, panel + resumen, sin cobro (todo el mundo en plan "Gratis").
2. **V1 — monetización**: Subscriptionify + gateway Stripe (el más rápido de integrar primero), pricing, facturación, gate de funciones por plan.
3. **V2 — automatización**: recordatorios por notificación (email + campana), importación de CSV, cotización de dólar automática, gateway MercadoPago sumado detrás de la misma interfaz.
4. **V3 — tiempo real y mobile**: Reverb en el panel, notificaciones push, PWA.

## 10. Consideraciones de seguridad

- Nunca guardar el PAN completo ni el CVV — solo lo que ya usa el diseño (últimos 4 dígitos), delegando todo dato sensible de pago a Stripe.
- Montos como `numeric(12,2)` en Postgres, nunca `float`.
- Rate limiting en login/registro (ya viene con Fortify) y en el endpoint de importación de estados de cuenta.
- Logs de auditoría básicos en cambios de contraseña, de plan y de método de pago.
