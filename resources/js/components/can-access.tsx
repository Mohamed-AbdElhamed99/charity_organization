import type { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import { ForbiddenContent } from '@/components/errors/forbidden-content';
import { usePermission } from '@/hooks/use-permission';
import { SUPER_ADMIN_ROLE } from '@/lib/authorization';

type CanAccessProps = {
    permission: string | string[];
    mode?: 'all' | 'any';
    children: ReactNode;
};

export function CanAccess({ permission, mode, children }: CanAccessProps) {
    const { can, hasRole } = usePermission();
    const requiredPermissions = Array.isArray(permission) ? permission : [permission];
    const resolvedMode = mode ?? (Array.isArray(permission) ? 'any' : 'all');

    const isAllowed = hasRole(SUPER_ADMIN_ROLE)
        || (resolvedMode === 'all'
            ? requiredPermissions.every((requiredPermission) => can(requiredPermission))
            : requiredPermissions.some((requiredPermission) => can(requiredPermission)));

    if (!isAllowed) {
        return (
            <>
                <Head title="Forbidden" />
                <ForbiddenContent />
            </>
        );
    }

    return <>{children}</>;
}
