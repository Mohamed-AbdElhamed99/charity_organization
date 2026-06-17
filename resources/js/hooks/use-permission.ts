import { useMemo } from 'react';
import { usePage } from '@inertiajs/react';
import { SUPER_ADMIN_ROLE } from '@/lib/authorization';
import type { Auth } from '@/types';

type SharedProps = {
    auth?: Auth;
};

type UsePermission = {
    can: (permission: string) => boolean;
    canAny: (permissions: string[]) => boolean;
    hasRole: (role: string) => boolean;
};

export function usePermission(): UsePermission {
    const { auth } = usePage<SharedProps>().props;

    const permissionSet = useMemo(
        () => new Set(auth?.permissions ?? []),
        [auth?.permissions],
    );
    const roleSet = useMemo(() => new Set(auth?.roles ?? []), [auth?.roles]);

    const isSuperAdmin = roleSet.has(SUPER_ADMIN_ROLE);

    const can = (permission: string): boolean => {
        if (isSuperAdmin) {
            return true;
        }

        return permissionSet.has(permission);
    };

    const canAny = (permissions: string[]): boolean => {
        if (isSuperAdmin) {
            return true;
        }

        return permissions.some((permission) => permissionSet.has(permission));
    };

    const hasRole = (role: string): boolean => {
        return roleSet.has(role);
    };

    return { can, canAny, hasRole };
}
