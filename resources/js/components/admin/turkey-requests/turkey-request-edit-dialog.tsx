import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
import InputError from '@/components/input-error'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

export type EditableTurkeyRequest = {
  id: number
  full_name: string
  email: string
  phone: string
  address: string | null
  quantity: number
  status: 'pending' | 'delivered' | 'cancelled'
}

type TurkeyRequestEditDialogProps = {
  request: EditableTurkeyRequest
  remaining: number
  onClose: () => void
}

export function TurkeyRequestEditDialog({
  request,
  remaining,
  onClose,
}: TurkeyRequestEditDialogProps) {
  const isPending = request.status === 'pending'
  const maxQuantity = remaining + request.quantity

  const form = useForm({
    full_name: request.full_name,
    email: request.email,
    phone: request.phone,
    address: request.address ?? '',
    quantity: request.quantity,
  })

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    form.patch(route('admin.turkey-requests.update', request.id), {
      preserveScroll: true,
      onSuccess: onClose,
    })
  }

  return (
    <Dialog open onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Edit request</DialogTitle>
          <DialogDescription>
            Update the beneficiary details and the number of turkeys reserved for them.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="edit-full-name">Full name</Label>
            <Input
              id="edit-full-name"
              value={form.data.full_name}
              onChange={(event) => form.setData('full_name', event.target.value)}
              required
            />
            <InputError message={form.errors.full_name} />
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="edit-email">Email</Label>
              <Input
                id="edit-email"
                type="email"
                value={form.data.email}
                onChange={(event) => form.setData('email', event.target.value)}
                required
              />
              <InputError message={form.errors.email} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="edit-phone">Phone</Label>
              <Input
                id="edit-phone"
                type="tel"
                value={form.data.phone}
                onChange={(event) => form.setData('phone', event.target.value)}
                required
              />
              <InputError message={form.errors.phone} />
            </div>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="edit-address">Address</Label>
            <Textarea
              id="edit-address"
              rows={3}
              value={form.data.address}
              onChange={(event) => form.setData('address', event.target.value)}
              required
            />
            <InputError message={form.errors.address} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="edit-quantity">Number of turkeys</Label>
            <Input
              id="edit-quantity"
              type="number"
              min={1}
              max={isPending ? maxQuantity : request.quantity}
              value={form.data.quantity}
              onChange={(event) => form.setData('quantity', Number(event.target.value))}
              disabled={!isPending}
              required
            />
            <p className="text-xs text-muted-foreground">
              {isPending
                ? `Up to ${maxQuantity} available for this request.`
                : 'Quantity can only be changed while the request is pending.'}
            </p>
            <InputError message={form.errors.quantity} />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={onClose}>
              Cancel
            </Button>
            <Button type="submit" disabled={form.processing}>
              Save changes
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
