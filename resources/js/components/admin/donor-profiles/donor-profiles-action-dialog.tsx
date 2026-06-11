import { useEffect } from 'react'
import { useForm } from '@inertiajs/react'
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
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/input-error'
import type {
  AvailableDonorUser,
  GeoOptions,
  SelectOption,
} from '@/types/models/donor-profile'

type DonorProfilesActionDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  availableUsers: AvailableDonorUser[]
  geoOptions: GeoOptions
  typeOptions: SelectOption[]
}

export function DonorProfilesActionDialog({
  open,
  onOpenChange,
  availableUsers,
  geoOptions,
  typeOptions,
}: DonorProfilesActionDialogProps) {
  const form = useForm({
    user_id: '',
    type: 'individual',
    organization_name: '',
    address: '',
    country_id: '',
    state_id: '',
    notes: '',
  })

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData({
      user_id: '',
      type: 'individual',
      organization_name: '',
      address: '',
      country_id: '',
      state_id: '',
      notes: '',
    })
  }, [open])

  const statesForCountry = geoOptions.states.filter(
    (state) => state.country_id.toString() === form.data.country_id
  )

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => ({
      user_id: data.user_id ? Number(data.user_id) : null,
      type: data.type,
      organization_name: data.organization_name || null,
      address: data.address || null,
      country_id: data.country_id ? Number(data.country_id) : null,
      state_id: data.state_id ? Number(data.state_id) : null,
      notes: data.notes || null,
    }))

    form.post(route('admin.donor-profiles.store'), {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    })
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
          <DialogTitle>Add Donor Profile</DialogTitle>
          <DialogDescription>
            Link a donor user account to a donor profile.
          </DialogDescription>
        </DialogHeader>

        <form
          id="donor-profile-form"
          onSubmit={handleSubmit}
          className="space-y-4"
        >
          <div className="grid gap-2">
            <Label htmlFor="user_id">Donor user</Label>
            <Select
              value={form.data.user_id}
              onValueChange={(value) => form.setData('user_id', value)}
            >
              <SelectTrigger id="user_id">
                <SelectValue placeholder="Select a donor user" />
              </SelectTrigger>
              <SelectContent>
                {availableUsers.map((user) => (
                  <SelectItem key={user.id} value={user.id.toString()}>
                    {user.name} ({user.email})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {availableUsers.length === 0 && (
              <p className="text-sm text-muted-foreground">
                No donor users without a profile are available.
              </p>
            )}
            <InputError message={form.errors.user_id} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="type">Type</Label>
            <Select
              value={form.data.type}
              onValueChange={(value) => {
                form.setData('type', value)
                if (value !== 'organization') {
                  form.setData('organization_name', '')
                }
              }}
            >
              <SelectTrigger id="type">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {typeOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.type} />
          </div>

          {form.data.type === 'organization' && (
            <div className="grid gap-2">
              <Label htmlFor="organization_name">Organization name</Label>
              <Input
                id="organization_name"
                value={form.data.organization_name}
                onChange={(event) =>
                  form.setData('organization_name', event.target.value)
                }
                required
              />
              <InputError message={form.errors.organization_name} />
            </div>
          )}

          <div className="grid gap-2">
            <Label htmlFor="address">Address</Label>
            <Textarea
              id="address"
              value={form.data.address}
              onChange={(event) => form.setData('address', event.target.value)}
              rows={2}
            />
            <InputError message={form.errors.address} />
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="country_id">Country</Label>
              <Select
                value={form.data.country_id}
                onValueChange={(value) => {
                  form.setData({
                    ...form.data,
                    country_id: value,
                    state_id: '',
                  })
                }}
              >
                <SelectTrigger id="country_id">
                  <SelectValue placeholder="Select country" />
                </SelectTrigger>
                <SelectContent>
                  {geoOptions.countries.map((country) => (
                    <SelectItem key={country.id} value={country.id.toString()}>
                      {country.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.country_id} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="state_id">State</Label>
              <Select
                value={form.data.state_id}
                onValueChange={(value) => form.setData('state_id', value)}
                disabled={!form.data.country_id}
              >
                <SelectTrigger id="state_id">
                  <SelectValue placeholder="Select state" />
                </SelectTrigger>
                <SelectContent>
                  {statesForCountry.map((state) => (
                    <SelectItem key={state.id} value={state.id.toString()}>
                      {state.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.state_id} />
            </div>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="notes">Notes</Label>
            <Textarea
              id="notes"
              value={form.data.notes}
              onChange={(event) => form.setData('notes', event.target.value)}
              rows={3}
            />
            <InputError message={form.errors.notes} />
          </div>
        </form>

        <DialogFooter>
          <Button
            type="submit"
            form="donor-profile-form"
            disabled={form.processing || availableUsers.length === 0}
          >
            Create profile
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
