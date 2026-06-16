import * as React from "react";

import { cn } from "@/lib/utils";

type SwitchProps = Omit<React.ComponentProps<"button">, "onChange"> & {
  checked: boolean;
  onCheckedChange: (checked: boolean) => void;
};

function Switch({
  className,
  checked,
  onCheckedChange,
  disabled,
  id,
  ...props
}: SwitchProps) {
  return (
    <button
      type="button"
      role="switch"
      id={id}
      aria-checked={checked}
      disabled={disabled}
      data-slot="switch"
      className={cn(
        "peer inline-flex h-6 w-11 shrink-0 items-center rounded-full border border-transparent shadow-xs transition-colors outline-none focus-visible:ring-2 focus-visible:ring-action-red/40 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50",
        checked ? "bg-action-red" : "bg-input",
        className,
      )}
      onClick={() => onCheckedChange(!checked)}
      {...props}
    >
      <span
        data-slot="switch-thumb"
        className={cn(
          "pointer-events-none block size-5 rounded-full bg-white shadow-sm ring-0 transition-transform",
          checked ? "translate-x-5 rtl:-translate-x-5" : "translate-x-0.5",
        )}
      />
    </button>
  );
}

export { Switch };
