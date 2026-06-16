import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";

export type CountryOption = {
  id: number;
  name: string;
};

export type DonorFieldErrors = Partial<
  Record<
    "first_name" | "last_name" | "email" | "phone" | "country_id" | "donor_message",
    string
  >
>;

type DonorFieldsProps = {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  countryId: string;
  isAnonymous: boolean;
  donorMessage: string;
  countries: CountryOption[];
  errors: DonorFieldErrors;
  labels: {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    country: string;
    countryPlaceholder: string;
    anonymousLabel: string;
    message: string;
    messagePlaceholder: string;
  };
  onFirstNameChange: (value: string) => void;
  onLastNameChange: (value: string) => void;
  onEmailChange: (value: string) => void;
  onPhoneChange: (value: string) => void;
  onCountryChange: (value: string) => void;
  onAnonymousChange: (value: boolean) => void;
  onMessageChange: (value: string) => void;
};

export function DonorFields({
  firstName,
  lastName,
  email,
  phone,
  countryId,
  isAnonymous,
  donorMessage,
  countries,
  errors,
  labels,
  onFirstNameChange,
  onLastNameChange,
  onEmailChange,
  onPhoneChange,
  onCountryChange,
  onAnonymousChange,
  onMessageChange,
}: DonorFieldsProps) {
  return (
    <div className="space-y-4">
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <Label htmlFor="first-name">{labels.firstName}</Label>
          <input
            id="first-name"
            required
            value={firstName}
            onChange={(event) => onFirstNameChange(event.target.value)}
            aria-invalid={Boolean(errors.first_name)}
            aria-describedby={errors.first_name ? "first-name-error" : undefined}
            className="mt-1 w-full rounded-lg border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-action-red focus:ring-2 focus:ring-action-red/20"
          />
          {errors.first_name ? (
            <p id="first-name-error" className="mt-1 text-xs text-action-red">
              {errors.first_name}
            </p>
          ) : null}
        </div>
        <div>
          <Label htmlFor="last-name">{labels.lastName}</Label>
          <input
            id="last-name"
            required
            value={lastName}
            onChange={(event) => onLastNameChange(event.target.value)}
            aria-invalid={Boolean(errors.last_name)}
            aria-describedby={errors.last_name ? "last-name-error" : undefined}
            className="mt-1 w-full rounded-lg border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-action-red focus:ring-2 focus:ring-action-red/20"
          />
          {errors.last_name ? (
            <p id="last-name-error" className="mt-1 text-xs text-action-red">
              {errors.last_name}
            </p>
          ) : null}
        </div>
      </div>

      <div>
        <Label htmlFor="email">{labels.email}</Label>
        <input
          id="email"
          type="email"
          required
          value={email}
          onChange={(event) => onEmailChange(event.target.value)}
          aria-invalid={Boolean(errors.email)}
          aria-describedby={errors.email ? "email-error" : undefined}
          className="mt-1 w-full rounded-lg border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-action-red focus:ring-2 focus:ring-action-red/20"
        />
        {errors.email ? (
          <p id="email-error" className="mt-1 text-xs text-action-red">
            {errors.email}
          </p>
        ) : null}
      </div>

      <div>
        <Label htmlFor="phone">{labels.phone}</Label>
        <input
          id="phone"
          type="tel"
          value={phone}
          onChange={(event) => onPhoneChange(event.target.value)}
          className="mt-1 w-full rounded-lg border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-action-red focus:ring-2 focus:ring-action-red/20"
        />
      </div>

      <div>
        <Label htmlFor="country">{labels.country}</Label>
        <Select value={countryId} onValueChange={onCountryChange}>
          <SelectTrigger id="country" className="mt-1 w-full">
            <SelectValue placeholder={labels.countryPlaceholder} />
          </SelectTrigger>
          <SelectContent>
            {countries.map((country) => (
              <SelectItem key={country.id} value={String(country.id)}>
                {country.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="flex items-start gap-3">
        <Checkbox
          id="anonymous"
          checked={isAnonymous}
          onCheckedChange={(value) => onAnonymousChange(value === true)}
        />
        <Label htmlFor="anonymous" className="text-sm leading-relaxed text-body-text">
          {labels.anonymousLabel}
        </Label>
      </div>

      <div>
        <Label htmlFor="message">{labels.message}</Label>
        <Textarea
          id="message"
          rows={3}
          value={donorMessage}
          onChange={(event) => onMessageChange(event.target.value)}
          placeholder={labels.messagePlaceholder}
          className="mt-1"
        />
      </div>
    </div>
  );
}

export default DonorFields;
