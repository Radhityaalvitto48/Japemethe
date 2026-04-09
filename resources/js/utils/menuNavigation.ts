const MENU_PATH_KEY = 'menu_path';

const isMenuPath = (path: string): boolean => {
    return path === '/menu' || path.startsWith('/s/');
};

export const getMenuPath = (): string => {
    if (typeof window === 'undefined') {
        return '/menu';
    }

    const pathname = window.location.pathname;

    if (isMenuPath(pathname)) {
        return pathname;
    }

    const storedPath = sessionStorage.getItem(MENU_PATH_KEY);

    if (storedPath && isMenuPath(storedPath)) {
        return storedPath;
    }

    return '/menu';
};

export const setMenuPath = (path?: string): void => {
    if (typeof window === 'undefined') {
        return;
    }

    const resolvedPath = path ?? window.location.pathname;

    if (isMenuPath(resolvedPath)) {
        sessionStorage.setItem(MENU_PATH_KEY, resolvedPath);
    }
};
