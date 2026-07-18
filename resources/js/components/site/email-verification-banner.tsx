import { useState } from "react";
import { router } from "@inertiajs/react";
import { route } from "ziggy-js";
import { useLocale } from "@/context/locale-context";

export function EmailVerificationBanner() {
  const { t, dir } = useLocale();
  const i18n = t.verificationBanner;
  const [sent, setSent] = useState(false);
  const [sending, setSending] = useState(false);

  const resend = () => {
    setSending(true);
    router.post(
      route("verification.send"),
      {},
      {
        preserveScroll: true,
        onFinish: () => setSending(false),
        onSuccess: () => setSent(true),
      },
    );
  };

  return (
    <div
      className="fixed inset-x-0 top-0 z-[60] flex h-10 items-center justify-center bg-amber-100 border-b border-amber-200 px-4 text-xs text-amber-800 sm:text-sm"
      dir={dir}
    >
      <div className="flex flex-wrap items-center justify-center gap-3">
        <p>{sent ? i18n.sent : i18n.message}</p>
        {!sent && (
          <button
            type="button"
            onClick={resend}
            disabled={sending}
            className="font-semibold underline disabled:opacity-60"
          >
            {i18n.resend}
          </button>
        )}
      </div>
    </div>
  );
}

export default EmailVerificationBanner;
