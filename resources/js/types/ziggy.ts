import { Config } from 'ziggy-js';

declare global {
    function route(): Config & { current: (name?: string, params?: any) => boolean | string | undefined };
    function route(name: string, params?: any, absolute?: boolean): string;
}

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        route: typeof route;
    }
}
