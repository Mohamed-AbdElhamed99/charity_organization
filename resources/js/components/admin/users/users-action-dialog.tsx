import { useEffect } from 'react'
import { router, useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import InputError from '@/components/input-error'
import PasswordInput from '@/components/password-input'
import { roleOptionsFromNames } from './data/data'
import { type User, type UserStatus } from '@/types/models/user'

type UsersActionDialogProps = {
  currentRow?: User
  open: boolean
  onOpenChange: (open: boolean) => void
  roles: string[]
}

const statusOptions: { label: string; value: UserStatus }[] = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Banned', value: 'banned' },
]

export function UsersActionDialog({
  currentRow,
  open,
  onOpenChange,
  roles,
}: UsersActionDialogProps) {
  const isEdit = !!currentRow
  const roleOptions = roleOptionsFromNames(roles)

  const form = useForm({
    name: currentRow?.name ?? '',
    email: currentRow?.email ?? '',
    phone: currentRow?.phone ?? '',
    status: currentRow?.status ?? 'active',
    role: currentRow?.role ?? '',
    password: '',
    password_confirmation: '',
    avatar: null as File | null,
  })

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData({
      name: currentRow?.name ?? '',
      email: currentRow?.email ?? '',
      phone: currentRow?.phone ?? '',
      status: currentRow?.status ?? 'active',
      role: currentRow?.role ?? '',
      password: '',
      password_confirmation: '',
      avatar: null,
    })
  }, [open, currentRow])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const options = {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    }

    if (isEdit && currentRow) {
      form.transform((data) => {
        if (!data.password) {
          const { password, password_confirmation, ...rest } = data
          return rest
        }

        return data
      })

      form.patch(route('admin.users.update', currentRow.id), options)
      return
    }

    form.post(route('admin.users.store'), options)
  }

  return (
    <Dialog
      open={open}
      onOpenChange={(state) => {
        if (!state) {
          form.reset()
          form.clearErrors()
        }
        onOpenChange(state)
      }}
    >
      <DialogContent className="sm:max-w-lg">
        <DialogHeader className="text-start">
          <DialogTitle>{isEdit ? 'Edit User' : 'Add New User'}</DialogTitle>
          <DialogDescription>
            {isEdit ? 'Update the user here. ' : 'Create new user here. '}
            Click save when you&apos;re done.
          </DialogDescription>
        </DialogHeader>

        <form
          id="user-form"
          onSubmit={handleSubmit}
          className="space-y-4"
        >
          <div className="grid gap-2">
            <Label htmlFor="name">Name</Label>
            <Input
              id="name"
              value={form.data.name}
              onChange={(event) => form.setData('name', event.target.value)}
              autoComplete="name"
              required
            />
            <InputError message={form.errors.name} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              value={form.data.email}
              onChange={(event) => form.setData('email', event.target.value)}
              autoComplete="email"
              required
            />
            <InputError message={form.errors.email} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="phone">Phone</Label>
            <Input
              id="phone"
              value={form.data.phone}
              onChange={(event) => form.setData('phone', event.target.value)}
              autoComplete="tel"
            />
            <InputError message={form.errors.phone} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="status">Status</Label>
            <Select
              value={form.data.status}
              onValueChange={(value) =>
                form.setData('status', value as UserStatus)
              }
            >
              <SelectTrigger id="status" className="w-full">
                <SelectValue placeholder="Select status" />
              </SelectTrigger>
              <SelectContent>
                {statusOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.status} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="role">Role</Label>
            <Select
              value={form.data.role}
              onValueChange={(value) => form.setData('role', value)}
            >
              <SelectTrigger id="role" className="w-full">
                <SelectValue placeholder="Select a role" />
              </SelectTrigger>
              <SelectContent>
                {roleOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.role} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="password">
              Password{isEdit ? ' (optional)' : ''}
            </Label>
            <PasswordInput
              id="password"
              value={form.data.password}
              onChange={(event) => form.setData('password', event.target.value)}
              autoComplete="new-password"
              required={!isEdit}
            />
            <InputError message={form.errors.password} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="password_confirmation">Confirm Password</Label>
            <PasswordInput
              id="password_confirmation"
              value={form.data.password_confirmation}
              onChange={(event) =>
                form.setData('password_confirmation', event.target.value)
              }
              autoComplete="new-password"
              required={!isEdit && !!form.data.password}
            />
            <InputError message={form.errors.password_confirmation} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="avatar">Avatar</Label>
            <Input
              id="avatar"
              type="file"
              accept="image/*"
              onChange={(event) =>
                form.setData('avatar', event.target.files?.[0] ?? null)
              }
            />
            <InputError message={form.errors.avatar} />
          </div>
        </form>

        <DialogFooter>
          <Button type="submit" form="user-form" disabled={form.processing}>
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
