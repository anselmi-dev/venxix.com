export function formatMoney(
    amount: string | number,
    currency: string,
): string {
    const symbol = currency === 'USD' ? 'U$' : '$';
    const num = Number(amount);

    return `${symbol} ${num.toLocaleString('es-UY', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

export function convertAmount(
    amount: number,
    fromCurrency: string,
    toCurrency: string,
    exchangeRate: string | null,
): number | null {
    if (fromCurrency === toCurrency) {
        return amount;
    }

    if (exchangeRate === null) {
        return null;
    }

    const rate = Number(exchangeRate);

    if (rate <= 0) {
        return null;
    }

    if (fromCurrency === 'USD' && toCurrency === 'UYU') {
        return amount * rate;
    }

    if (fromCurrency === 'UYU' && toCurrency === 'USD') {
        return amount / rate;
    }

    return null;
}

export function secondaryAmountLabel(
    amount: string | number,
    currency: string,
    preferredCurrency: string,
    exchangeRate: string | null,
): string | null {
    const converted = convertAmount(
        Number(amount),
        currency,
        preferredCurrency === 'USD' ? 'UYU' : 'USD',
        exchangeRate,
    );

    if (converted === null) {
        return null;
    }

    const secondaryCurrency = preferredCurrency === 'USD' ? 'UYU' : 'USD';

    return `≈ ${formatMoney(converted, secondaryCurrency)}`;
}

export function combinedPendingTotal(
    pendingUyu: string,
    pendingUsd: string,
    preferredCurrency: string,
    exchangeRate: string | null,
): string {
    const uyu = Number(pendingUyu);
    const usd = Number(pendingUsd);

    if (preferredCurrency === 'USD') {
        const convertedUyu = convertAmount(uyu, 'UYU', 'USD', exchangeRate) ?? 0;

        return (usd + convertedUyu).toFixed(2);
    }

    const convertedUsd = convertAmount(usd, 'USD', 'UYU', exchangeRate) ?? 0;

    return (uyu + convertedUsd).toFixed(2);
}
