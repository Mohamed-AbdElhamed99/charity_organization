import { FormEvent } from 'react'
import { Head, useForm, usePage } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

type AidItem = {
  id: number
  name: { en?: string; ar?: string }
  unit: { en?: string; ar?: string } | null
  default_unit_cost: number | null
  category: string | null
  is_active: boolean
}

type PageProps = {
  items: {
    data: AidItem[]
  }
}

export default function AidItemsIndex() {
  const { items } = usePage<PageProps>().props
  const { data, setData, post, processing, reset } = useForm({
    name: { en: '', ar: '' },
    unit: { en: '', ar: '' },
    default_unit_cost: '' as string | number,
    category: '',
    is_active: true,
  })

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    post(route('admin.aid-items.store'), {
      preserveScroll: true,
      onSuccess: () => reset(),
    })
  }

  return (
    <>
      <Head title="Aid Items" />
      <Main className="flex flex-1 flex-col gap-6">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Aid Item Catalog</h2>
          <p className="text-muted-foreground">
            Manage supportable item names and default unit costs.
          </p>
        </div>

        <form onSubmit={submit} className="grid gap-4 rounded-lg border p-4 md:grid-cols-3">
          <div className="space-y-2">
            <Label htmlFor="name-en">Name (EN)</Label>
            <Input
              id="name-en"
              value={data.name.en}
              onChange={(event) => setData('name', { ...data.name, en: event.target.value })}
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="name-ar">Name (AR)</Label>
            <Input
              id="name-ar"
              value={data.name.ar}
              onChange={(event) => setData('name', { ...data.name, ar: event.target.value })}
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="category">Category</Label>
            <Input
              id="category"
              value={data.category}
              onChange={(event) => setData('category', event.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="unit-en">Unit (EN)</Label>
            <Input
              id="unit-en"
              value={data.unit.en}
              onChange={(event) => setData('unit', { ...data.unit, en: event.target.value })}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="unit-ar">Unit (AR)</Label>
            <Input
              id="unit-ar"
              value={data.unit.ar}
              onChange={(event) => setData('unit', { ...data.unit, ar: event.target.value })}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="default-cost">Default Unit Cost (cents)</Label>
            <Input
              id="default-cost"
              type="number"
              min={0}
              value={data.default_unit_cost}
              onChange={(event) => setData('default_unit_cost', event.target.value)}
            />
          </div>
          <div className="md:col-span-3">
            <Button type="submit" disabled={processing}>Create item</Button>
          </div>
        </form>

        <div className="overflow-hidden rounded-lg border">
          <table className="w-full text-sm">
            <thead className="bg-muted/50 text-left">
              <tr>
                <th className="px-4 py-3">Name</th>
                <th className="px-4 py-3">Unit</th>
                <th className="px-4 py-3">Default cost (cents)</th>
                <th className="px-4 py-3">Category</th>
                <th className="px-4 py-3">Active</th>
              </tr>
            </thead>
            <tbody>
              {items.data.map((item) => (
                <tr key={item.id} className="border-t">
                  <td className="px-4 py-3">{item.name.en ?? item.name.ar ?? '-'}</td>
                  <td className="px-4 py-3">{item.unit?.en ?? item.unit?.ar ?? '-'}</td>
                  <td className="px-4 py-3">{item.default_unit_cost ?? '-'}</td>
                  <td className="px-4 py-3">{item.category ?? '-'}</td>
                  <td className="px-4 py-3">{item.is_active ? 'Yes' : 'No'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Main>
    </>
  )
}
