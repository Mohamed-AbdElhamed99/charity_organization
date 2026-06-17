import { Head } from '@inertiajs/react';
import { ForbiddenContent } from '@/components/errors/forbidden-content';

export default function Forbidden() {
    return (
        <>
            <Head title="Forbidden" />
            <ForbiddenContent />
        </>
    );
}
