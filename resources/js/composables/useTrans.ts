import { usePage } from '@inertiajs/vue3';

type TranslationTree = Record<string, unknown>;

function lookup(tree: unknown, key: string): unknown {
    return key.split('.').reduce<unknown>((current, part) => {
        if (
            current &&
            typeof current === 'object' &&
            part in (current as TranslationTree)
        ) {
            return (current as TranslationTree)[part];
        }

        return undefined;
    }, tree);
}

export function useTrans() {
    const page = usePage();

    function t(
        key: string,
        replace: Record<string, string | number> = {},
    ): string {
        const raw = lookup(page.props.translations, key);
        let value = typeof raw === 'string' ? raw : key;

        Object.entries(replace).forEach(([name, replacement]) => {
            value = value.replaceAll(`:${name}`, String(replacement));
        });

        return value;
    }

    function tList(key: string): string[] {
        const raw = lookup(page.props.translations, key);

        return Array.isArray(raw) ? raw.map(String) : [];
    }

    return { t, tList };
}
