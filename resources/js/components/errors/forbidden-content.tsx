import { Link } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import { Main } from '@/components/layout/main';
import { Button } from '@/components/ui/button';

export function ForbiddenContent() {
    return (
        <Main className="flex min-h-[60vh] items-center justify-center">
            <div className="w-full max-w-lg rounded-lg border bg-card p-8 text-center">
                <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                    <ShieldAlert className="h-6 w-6 text-muted-foreground" />
                </div>

                <h1 className="text-2xl font-semibold">Access denied</h1>
                <p className="mt-2 text-sm text-muted-foreground">
                    You don't have permission to view this page.
                </p>

                <div className="mt-6">
                    <Button asChild>
                        <Link href="/admin/dashboard">Go to dashboard</Link>
                    </Button>
                </div>
            </div>
        </Main>
    );
}
