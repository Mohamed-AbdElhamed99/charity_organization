import { Head, Link, router, usePage } from '@inertiajs/react'
import { useState } from 'react'
import { route } from 'ziggy-js'
import { TurkeyRequestEditDialog } from '@/components/admin/turkey-requests/turkey-request-edit-dialog'
import { Main } from '@/components/layout/main'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { index as turkeyRequestsIndex } from '@/routes/admin/turkey-requests'

type TurkeyRequestStatus = 'pending' | 'delivered' | 'cancelled'

type TurkeyRequestRow = {
  id: number
  beneficiary_id: number
  full_name: string
  email: string
  phone: string
  address: string | null
  quantity: number
  code: string
  status: TurkeyRequestStatus
  created_at: string | null
  delivered_at: string | null
}

type PageProps = {
  requests: TurkeyRequestRow[]
  stats: {
    total: number
    remaining: number
    pending: number
    delivered: number
  }
}

const statusVariant: Record<TurkeyRequestStatus, 'default' | 'secondary' | 'outline'> = {
  pending: 'secondary',
  delivered: 'default',
  cancelled: 'outline',
}

function formatDate(value: string | null): string {
  return value ? new Date(value).toLocaleString() : '—'
}

export default function TurkeyRequestsIndex() {
  const { requests, stats } = usePage<PageProps>().props
  const [editingId, setEditingId] = useState<number | null>(null)
  const editingRequest = requests.find((request) => request.id === editingId) ?? null

  const summary = [
    { label: 'Total turkeys', value: stats.total },
    { label: 'Remaining', value: stats.remaining },
    { label: 'Pending delivery', value: stats.pending },
    { label: 'Delivered', value: stats.delivered },
  ]

  const markDelivered = (request: TurkeyRequestRow) => {
    if (!confirm(`Mark ${request.quantity} turkey(s) for ${request.full_name} as delivered?`)) {
      return
    }

    router.patch(route('admin.turkey-requests.mark-delivered', request.id), {}, { preserveScroll: true })
  }

  const cancel = (request: TurkeyRequestRow) => {
    if (!confirm(`Cancel ${request.full_name}'s request? The turkey becomes available again.`)) {
      return
    }

    router.patch(route('admin.turkey-requests.cancel', request.id), {}, { preserveScroll: true })
  }

  return (
    <>
      <Head title="Turkey Requests" />

      <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Free Halal Turkey Requests</h2>
          <p className="text-muted-foreground">
            Give each beneficiary their code at delivery. They confirm receipt on the QR link.
          </p>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {summary.map((item) => (
            <Card key={item.label}>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                  {item.label}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-3xl font-bold">{item.value}</p>
              </CardContent>
            </Card>
          ))}
        </div>

        <div className="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Address</TableHead>
                <TableHead>Qty</TableHead>
                <TableHead>Code</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Requested</TableHead>
                <TableHead>Delivered</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {requests.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={10} className="h-24 text-center text-muted-foreground">
                    No requests yet.
                  </TableCell>
                </TableRow>
              ) : (
                requests.map((request) => (
                  <TableRow key={request.id}>
                    <TableCell className="font-medium">
                      <Link
                        href={route('admin.beneficiaries.show', request.beneficiary_id)}
                        className="hover:underline"
                      >
                        {request.full_name}
                      </Link>
                    </TableCell>
                    <TableCell>{request.phone}</TableCell>
                    <TableCell>{request.email}</TableCell>
                    <TableCell className="max-w-56 whitespace-normal">{request.address ?? '—'}</TableCell>
                    <TableCell className="font-semibold">{request.quantity}</TableCell>
                    <TableCell className="font-mono">{request.code}</TableCell>
                    <TableCell>
                      <Badge variant={statusVariant[request.status]} className="capitalize">
                        {request.status}
                      </Badge>
                    </TableCell>
                    <TableCell>{formatDate(request.created_at)}</TableCell>
                    <TableCell>{formatDate(request.delivered_at)}</TableCell>
                    <TableCell className="text-right">
                      {request.status !== 'cancelled' ? (
                        <div className="flex justify-end gap-2">
                          <Button size="sm" variant="outline" onClick={() => setEditingId(request.id)}>
                            Edit
                          </Button>
                          {request.status === 'pending' ? (
                            <>
                              <Button size="sm" onClick={() => markDelivered(request)}>
                                Mark delivered
                              </Button>
                              <Button size="sm" variant="outline" onClick={() => cancel(request)}>
                                Cancel
                              </Button>
                            </>
                          ) : null}
                        </div>
                      ) : null}
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>

        {editingRequest ? (
          <TurkeyRequestEditDialog
            key={editingRequest.id}
            request={editingRequest}
            remaining={stats.remaining}
            onClose={() => setEditingId(null)}
          />
        ) : null}
      </Main>
    </>
  )
}

TurkeyRequestsIndex.layout = {
  breadcrumbs: [
    {
      title: 'Turkey Requests',
      href: turkeyRequestsIndex.url(),
    },
  ],
}
