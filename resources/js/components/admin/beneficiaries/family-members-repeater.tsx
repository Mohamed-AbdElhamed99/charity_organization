import { Plus, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
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
import {
  emptyFamilyMember,
  genderOptions,
  subtypeOptions,
} from './data/data'

type FamilyMemberFormData = ReturnType<typeof emptyFamilyMember> & { id?: number }

type FamilyMembersRepeaterProps = {
  members: FamilyMemberFormData[]
  errors: Record<string, string>
  onChange: (members: FamilyMemberFormData[]) => void
}

export function FamilyMembersRepeater({
  members,
  errors,
  onChange,
}: FamilyMembersRepeaterProps) {
  const updateMember = (
    index: number,
    field: keyof FamilyMemberFormData,
    value: string
  ) => {
    const next = [...members]
    next[index] = { ...next[index], [field]: value }
    onChange(next)
  }

  const addMember = () => {
    onChange([...members, emptyFamilyMember()])
  }

  const removeMember = (index: number) => {
    onChange(members.filter((_, memberIndex) => memberIndex !== index))
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-semibold">Family Members</h3>
        <Button type="button" variant="outline" size="sm" onClick={addMember}>
          <Plus className="me-2 size-4" />
          Add member
        </Button>
      </div>

      {members.map((member, index) => (
        <div
          key={member.id ?? `new-${index}`}
          className="space-y-4 rounded-lg border p-4"
        >
          <div className="flex items-center justify-between">
            <p className="font-medium">Member {index + 1}</p>
            {members.length > 1 && (
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() => removeMember(index)}
              >
                <Trash2 className="size-4 text-destructive" />
                <span className="sr-only">Remove member</span>
              </Button>
            )}
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor={`member-${index}-subtype`}>Subtype</Label>
              <Select
                value={member.subtype}
                onValueChange={(value) => updateMember(index, 'subtype', value)}
              >
                <SelectTrigger id={`member-${index}-subtype`}>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {subtypeOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={errors[`family.members.${index}.subtype`]} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor={`member-${index}-relation`}>Relation</Label>
              <Input
                id={`member-${index}-relation`}
                value={member.relation}
                onChange={(event) =>
                  updateMember(index, 'relation', event.target.value)
                }
                placeholder="Head, Spouse, Son..."
              />
              <InputError message={errors[`family.members.${index}.relation`]} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor={`member-${index}-first_name`}>First name</Label>
              <Input
                id={`member-${index}-first_name`}
                value={member.first_name}
                onChange={(event) =>
                  updateMember(index, 'first_name', event.target.value)
                }
                dir="auto"
              />
              <InputError
                message={errors[`family.members.${index}.first_name`]}
              />
            </div>

            <div className="grid gap-2">
              <Label htmlFor={`member-${index}-last_name`}>Last name</Label>
              <Input
                id={`member-${index}-last_name`}
                value={member.last_name}
                onChange={(event) =>
                  updateMember(index, 'last_name', event.target.value)
                }
                dir="auto"
              />
            </div>

            <div className="grid gap-2">
              <Label htmlFor={`member-${index}-gender`}>Gender</Label>
              <Select
                value={member.gender || undefined}
                onValueChange={(value) => updateMember(index, 'gender', value)}
              >
                <SelectTrigger id={`member-${index}-gender`}>
                  <SelectValue placeholder="Select gender" />
                </SelectTrigger>
                <SelectContent>
                  {genderOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="grid gap-2">
              <Label htmlFor={`member-${index}-birthdate`}>Date of birth</Label>
              <Input
                id={`member-${index}-birthdate`}
                type="date"
                value={member.birthdate}
                onChange={(event) =>
                  updateMember(index, 'birthdate', event.target.value)
                }
              />
            </div>

            <div className="grid gap-2 md:col-span-2">
              <Label htmlFor={`member-${index}-national_id`}>National ID</Label>
              <Input
                id={`member-${index}-national_id`}
                value={member.national_id}
                onChange={(event) =>
                  updateMember(index, 'national_id', event.target.value)
                }
                dir="ltr"
              />
            </div>
          </div>

          {member.subtype === 'child' && (
            <div className="grid gap-2">
              <Label htmlFor={`member-${index}-school_year`}>School year</Label>
              <Input
                id={`member-${index}-school_year`}
                value={member.school_year}
                onChange={(event) =>
                  updateMember(index, 'school_year', event.target.value)
                }
              />
            </div>
          )}

          <div className="grid gap-2">
            <Label htmlFor={`member-${index}-behavior_notes`}>Notes</Label>
            <Textarea
              id={`member-${index}-behavior_notes`}
              value={member.behavior_notes}
              onChange={(event) =>
                updateMember(index, 'behavior_notes', event.target.value)
              }
              rows={2}
            />
          </div>
        </div>
      ))}
    </div>
  )
}
