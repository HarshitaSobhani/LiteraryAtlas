export function debounce<T extends (...args: any[]) => void>(fn: T, wait = 300) {
    let timer: ReturnType<typeof setTimeout> | undefined;
    const wrapped = (...args: Parameters<T>) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), wait);
    };
    wrapped.cancel = () => clearTimeout(timer);
    return wrapped;
}
