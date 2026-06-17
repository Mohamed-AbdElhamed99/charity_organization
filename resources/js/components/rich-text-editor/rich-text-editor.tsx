import { useEffect, useRef, useState } from 'react'
import { EditorContent, useEditor, useEditorState } from '@tiptap/react'
import type { Editor } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import UnderlineExt from '@tiptap/extension-underline'
import LinkExt from '@tiptap/extension-link'
import ImageExt from '@tiptap/extension-image'
import TextAlignExt from '@tiptap/extension-text-align'
import PlaceholderExt from '@tiptap/extension-placeholder'
import CharacterCountExt from '@tiptap/extension-character-count'
import {
  AlignCenter,
  AlignJustify,
  AlignLeft,
  AlignRight,
  Bold,
  Code,
  Heading2,
  Heading3,
  Heading4,
  Image as ImageIcon,
  Italic,
  Link2,
  Link2Off,
  List,
  ListOrdered,
  Minus,
  Pilcrow,
  Quote,
  Redo2,
  Strikethrough,
  Underline as UnderlineIcon,
  Undo2,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import { Toggle } from '@/components/ui/toggle'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { cn } from '@/lib/utils'

// ─── Public Props ─────────────────────────────────────────────────────────────

export type RichTextEditorProps = {
  value: string
  onChange: (html: string) => void
  /** Set explicitly per locale — never inherited from the page direction. */
  dir: 'rtl' | 'ltr'
  placeholder?: string
  disabled?: boolean
  minHeight?: string
  /** When provided, CharacterCount extension enforces this limit. */
  maxLength?: number
}

// ─── Internal: Toolbar Toggle ─────────────────────────────────────────────────

type ToolbarToggleProps = {
  pressed: boolean
  onToggle: () => void
  label: string
  disabled?: boolean
  children: React.ReactNode
}

function ToolbarToggle({ pressed, onToggle, label, disabled, children }: ToolbarToggleProps) {
  return (
    <Tooltip>
      <TooltipTrigger asChild>
        <Toggle
          size="sm"
          pressed={pressed}
          onPressedChange={onToggle}
          disabled={disabled}
          aria-label={label}
        >
          {children}
        </Toggle>
      </TooltipTrigger>
      <TooltipContent>{label}</TooltipContent>
    </Tooltip>
  )
}

// ─── Internal: Heading Select ─────────────────────────────────────────────────

type HeadingLevel = 2 | 3 | 4

type HeadingSelectProps = {
  editor: Editor
  headingLevel: HeadingLevel | null
  disabled?: boolean
}

function HeadingSelect({ editor, headingLevel, disabled }: HeadingSelectProps) {
  const currentValue = headingLevel ? `h${headingLevel}` : 'paragraph'

  function handleValueChange(value: string) {
    if (value === 'paragraph') {
      editor.chain().focus().setParagraph().run()
    } else {
      const level = parseInt(value.slice(1)) as HeadingLevel
      editor.chain().focus().toggleHeading({ level }).run()
    }
  }

  return (
    <Select value={currentValue} onValueChange={handleValueChange} disabled={disabled}>
      <Tooltip>
        <TooltipTrigger asChild>
          <SelectTrigger size="sm" className="w-32 focus:ring-0 focus:ring-offset-0">
            <SelectValue />
          </SelectTrigger>
        </TooltipTrigger>
        <TooltipContent>Text style</TooltipContent>
      </Tooltip>
      <SelectContent>
        <SelectItem value="paragraph">
          <span className="flex items-center gap-1.5">
            <Pilcrow className="size-3.5" /> Paragraph
          </span>
        </SelectItem>
        <SelectItem value="h2">
          <span className="flex items-center gap-1.5">
            <Heading2 className="size-3.5" /> Heading 2
          </span>
        </SelectItem>
        <SelectItem value="h3">
          <span className="flex items-center gap-1.5">
            <Heading3 className="size-3.5" /> Heading 3
          </span>
        </SelectItem>
        <SelectItem value="h4">
          <span className="flex items-center gap-1.5">
            <Heading4 className="size-3.5" /> Heading 4
          </span>
        </SelectItem>
      </SelectContent>
    </Select>
  )
}

// ─── Internal: Link Popover ───────────────────────────────────────────────────

type LinkPopoverProps = {
  editor: Editor
  isActive: boolean
  currentHref: string | undefined
  currentTarget: string | undefined
  disabled?: boolean
}

function LinkPopover({ editor, isActive, currentHref, currentTarget, disabled }: LinkPopoverProps) {
  const [open, setOpen] = useState(false)
  const [url, setUrl] = useState('')
  const [openInNewTab, setOpenInNewTab] = useState(true)

  function handleOpenChange(next: boolean) {
    if (next) {
      setUrl(currentHref ?? '')
      setOpenInNewTab(currentTarget === '_blank')
    }
    setOpen(next)
  }

  function applyLink() {
    if (!url.trim()) {
      editor.chain().focus().extendMarkRange('link').unsetLink().run()
    } else {
      editor
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url.trim(), target: openInNewTab ? '_blank' : null })
        .run()
    }
    setOpen(false)
  }

  function removeLink() {
    editor.chain().focus().extendMarkRange('link').unsetLink().run()
    setOpen(false)
  }

  return (
    <Popover open={open} onOpenChange={handleOpenChange}>
      <Tooltip>
        <TooltipTrigger asChild>
          <PopoverTrigger asChild>
            <Toggle size="sm" pressed={isActive} disabled={disabled} aria-label="Insert link">
              <Link2 />
            </Toggle>
          </PopoverTrigger>
        </TooltipTrigger>
        <TooltipContent>Insert link</TooltipContent>
      </Tooltip>
      <PopoverContent className="w-80" align="start">
        <div className="space-y-3">
          <div className="grid gap-1.5">
            <Label htmlFor="rte-link-url">URL</Label>
            <Input
              id="rte-link-url"
              value={url}
              onChange={(e) => setUrl(e.target.value)}
              placeholder="https://example.com"
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  e.preventDefault()
                  applyLink()
                }
              }}
            />
          </div>
          <div className="flex items-center gap-2">
            <Checkbox
              id="rte-link-new-tab"
              checked={openInNewTab}
              onCheckedChange={(checked) => setOpenInNewTab(checked === true)}
            />
            <Label htmlFor="rte-link-new-tab" className="font-normal">
              Open in new tab
            </Label>
          </div>
          <div className="flex items-center gap-2">
            <Button type="button" size="sm" onClick={applyLink}>
              Apply
            </Button>
            {isActive && (
              <Button type="button" size="sm" variant="ghost" onClick={removeLink}>
                <Link2Off className="size-3.5" />
                Remove link
              </Button>
            )}
          </div>
        </div>
      </PopoverContent>
    </Popover>
  )
}

// ─── Internal: Image Popover ──────────────────────────────────────────────────

type ImagePopoverProps = {
  editor: Editor
  disabled?: boolean
}

function ImagePopover({ editor, disabled }: ImagePopoverProps) {
  const [open, setOpen] = useState(false)
  const [url, setUrl] = useState('')
  const [alt, setAlt] = useState('')

  function handleOpenChange(next: boolean) {
    if (!next) {
      setUrl('')
      setAlt('')
    }
    setOpen(next)
  }

  function insertImage() {
    if (!url.trim()) return
    editor.chain().focus().setImage({ src: url.trim(), alt: alt.trim() }).run()
    setOpen(false)
    setUrl('')
    setAlt('')
  }

  return (
    <Popover open={open} onOpenChange={handleOpenChange}>
      <Tooltip>
        <TooltipTrigger asChild>
          <PopoverTrigger asChild>
            <Toggle
              size="sm"
              pressed={false}
              disabled={disabled}
              aria-label="Insert image by URL"
            >
              <ImageIcon />
            </Toggle>
          </PopoverTrigger>
        </TooltipTrigger>
        <TooltipContent>Insert image (URL)</TooltipContent>
      </Tooltip>
      <PopoverContent className="w-80" align="start">
        <div className="space-y-3">
          <div className="grid gap-1.5">
            <Label htmlFor="rte-img-url">Image URL</Label>
            <Input
              id="rte-img-url"
              value={url}
              onChange={(e) => setUrl(e.target.value)}
              placeholder="https://example.com/image.jpg"
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  e.preventDefault()
                  insertImage()
                }
              }}
            />
          </div>
          <div className="grid gap-1.5">
            <Label htmlFor="rte-img-alt">Alt text</Label>
            <Input
              id="rte-img-alt"
              value={alt}
              onChange={(e) => setAlt(e.target.value)}
              placeholder="Describe the image"
            />
          </div>
          <Button type="button" size="sm" onClick={insertImage} disabled={!url.trim()}>
            Insert image
          </Button>
        </div>
      </PopoverContent>
    </Popover>
  )
}

// ─── Internal: Character Count Display ───────────────────────────────────────

type CharacterCountDisplayProps = {
  editor: Editor
  maxLength: number
}

function CharacterCountDisplay({ editor, maxLength }: CharacterCountDisplayProps) {
  const characters = useEditorState({
    editor,
    selector: (ctx) =>
      (ctx.editor.storage.characterCount as { characters: () => number }).characters(),
  })

  return (
    <div className="border-t px-3 py-1 text-right text-xs text-muted-foreground">
      <span className={cn(characters >= maxLength && 'font-medium text-destructive')}>
        {characters}
      </span>
      {' / '}
      {maxLength}
    </div>
  )
}

// ─── Internal: Toolbar ────────────────────────────────────────────────────────

type ToolbarProps = {
  editor: Editor
  disabled?: boolean
}

function Toolbar({ editor, disabled }: ToolbarProps) {
  const state = useEditorState({
    editor,
    selector: (ctx) => ({
      isBold: ctx.editor.isActive('bold'),
      isItalic: ctx.editor.isActive('italic'),
      isUnderline: ctx.editor.isActive('underline'),
      isStrike: ctx.editor.isActive('strike'),
      headingLevel: ctx.editor.isActive('heading', { level: 2 })
        ? (2 as HeadingLevel)
        : ctx.editor.isActive('heading', { level: 3 })
          ? (3 as HeadingLevel)
          : ctx.editor.isActive('heading', { level: 4 })
            ? (4 as HeadingLevel)
            : null,
      isBulletList: ctx.editor.isActive('bulletList'),
      isOrderedList: ctx.editor.isActive('orderedList'),
      isBlockquote: ctx.editor.isActive('blockquote'),
      isCodeBlock: ctx.editor.isActive('codeBlock'),
      isAlignLeft: ctx.editor.isActive({ textAlign: 'left' }),
      isAlignCenter: ctx.editor.isActive({ textAlign: 'center' }),
      isAlignRight: ctx.editor.isActive({ textAlign: 'right' }),
      isAlignJustify: ctx.editor.isActive({ textAlign: 'justify' }),
      isLink: ctx.editor.isActive('link'),
      linkHref: ctx.editor.getAttributes('link').href as string | undefined,
      linkTarget: ctx.editor.getAttributes('link').target as string | undefined,
      canUndo: ctx.editor.can().undo(),
      canRedo: ctx.editor.can().redo(),
    }),
  })

  return (
    <div className="flex flex-wrap items-center gap-0.5 p-1.5" role="toolbar" aria-label="Text formatting">
      {/* Formatting */}
      <ToolbarToggle
        pressed={state.isBold}
        onToggle={() => editor.chain().focus().toggleBold().run()}
        label="Bold"
        disabled={disabled}
      >
        <Bold />
      </ToolbarToggle>
      <ToolbarToggle
        pressed={state.isItalic}
        onToggle={() => editor.chain().focus().toggleItalic().run()}
        label="Italic"
        disabled={disabled}
      >
        <Italic />
      </ToolbarToggle>
      <ToolbarToggle
        pressed={state.isUnderline}
        onToggle={() => editor.chain().focus().toggleUnderline().run()}
        label="Underline"
        disabled={disabled}
      >
        <UnderlineIcon />
      </ToolbarToggle>
      <ToolbarToggle
        pressed={state.isStrike}
        onToggle={() => editor.chain().focus().toggleStrike().run()}
        label="Strikethrough"
        disabled={disabled}
      >
        <Strikethrough />
      </ToolbarToggle>

      <Separator orientation="vertical" className="mx-0.5 h-6" />

      {/* Headings */}
      <HeadingSelect editor={editor} headingLevel={state.headingLevel} disabled={disabled} />

      <Separator orientation="vertical" className="mx-0.5 h-6" />

      {/* Lists */}
      <ToolbarToggle
        pressed={state.isBulletList}
        onToggle={() => editor.chain().focus().toggleBulletList().run()}
        label="Bullet list"
        disabled={disabled}
      >
        <List />
      </ToolbarToggle>
      <ToolbarToggle
        pressed={state.isOrderedList}
        onToggle={() => editor.chain().focus().toggleOrderedList().run()}
        label="Ordered list"
        disabled={disabled}
      >
        <ListOrdered />
      </ToolbarToggle>

      <Separator orientation="vertical" className="mx-0.5 h-6" />

      {/* Blocks */}
      <ToolbarToggle
        pressed={state.isBlockquote}
        onToggle={() => editor.chain().focus().toggleBlockquote().run()}
        label="Blockquote"
        disabled={disabled}
      >
        <Quote />
      </ToolbarToggle>
      <ToolbarToggle
        pressed={state.isCodeBlock}
        onToggle={() => editor.chain().focus().toggleCodeBlock().run()}
        label="Code block"
        disabled={disabled}
      >
        <Code />
      </ToolbarToggle>
      <Tooltip>
        <TooltipTrigger asChild>
          <Toggle
            size="sm"
            pressed={false}
            onPressedChange={() => editor.chain().focus().setHorizontalRule().run()}
            disabled={disabled}
            aria-label="Horizontal rule"
          >
            <Minus />
          </Toggle>
        </TooltipTrigger>
        <TooltipContent>Horizontal rule</TooltipContent>
      </Tooltip>

      <Separator orientation="vertical" className="mx-0.5 h-6" />

      {/* Alignment */}
      <ToolbarToggle
        pressed={state.isAlignLeft}
        onToggle={() => editor.chain().focus().setTextAlign('left').run()}
        label="Align left"
        disabled={disabled}
      >
        <AlignLeft />
      </ToolbarToggle>
      <ToolbarToggle
        pressed={state.isAlignCenter}
        onToggle={() => editor.chain().focus().setTextAlign('center').run()}
        label="Align center"
        disabled={disabled}
      >
        <AlignCenter />
      </ToolbarToggle>
      <ToolbarToggle
        pressed={state.isAlignRight}
        onToggle={() => editor.chain().focus().setTextAlign('right').run()}
        label="Align right"
        disabled={disabled}
      >
        <AlignRight />
      </ToolbarToggle>
      <ToolbarToggle
        pressed={state.isAlignJustify}
        onToggle={() => editor.chain().focus().setTextAlign('justify').run()}
        label="Align justify"
        disabled={disabled}
      >
        <AlignJustify />
      </ToolbarToggle>

      <Separator orientation="vertical" className="mx-0.5 h-6" />

      {/* Link & Image */}
      <LinkPopover
        editor={editor}
        isActive={state.isLink}
        currentHref={state.linkHref}
        currentTarget={state.linkTarget}
        disabled={disabled}
      />
      <ImagePopover editor={editor} disabled={disabled} />

      <Separator orientation="vertical" className="mx-0.5 h-6" />

      {/* History */}
      <Tooltip>
        <TooltipTrigger asChild>
          <Button
            type="button"
            variant="ghost"
            size="sm"
            onClick={() => editor.chain().focus().undo().run()}
            disabled={disabled || !state.canUndo}
            aria-label="Undo"
            className="size-8 p-0"
          >
            <Undo2 />
          </Button>
        </TooltipTrigger>
        <TooltipContent>Undo</TooltipContent>
      </Tooltip>
      <Tooltip>
        <TooltipTrigger asChild>
          <Button
            type="button"
            variant="ghost"
            size="sm"
            onClick={() => editor.chain().focus().redo().run()}
            disabled={disabled || !state.canRedo}
            aria-label="Redo"
            className="size-8 p-0"
          >
            <Redo2 />
          </Button>
        </TooltipTrigger>
        <TooltipContent>Redo</TooltipContent>
      </Tooltip>
    </div>
  )
}

// ─── Main Component ───────────────────────────────────────────────────────────

export function RichTextEditor({
  value,
  onChange,
  dir,
  placeholder,
  disabled = false,
  minHeight = '12rem',
  maxLength,
}: RichTextEditorProps) {
  // Keep onChange current without re-creating the editor when the prop changes.
  const onChangeRef = useRef(onChange)
  onChangeRef.current = onChange

  const editor = useEditor(
    {
      extensions: [
        StarterKit.configure({
          // Restrict to H2–H4 only; H1 belongs to the page/post title.
          heading: { levels: [2, 3, 4] },
        }),
        UnderlineExt,
        LinkExt.configure({
          openOnClick: false,
          HTMLAttributes: { rel: 'noopener noreferrer' },
        }),
        ImageExt.configure({ inline: false, allowBase64: false }),
        TextAlignExt.configure({ types: ['heading', 'paragraph'] }),
        PlaceholderExt.configure({ placeholder }),
        CharacterCountExt.configure({ limit: maxLength }),
      ],
      content: value || '',
      editorProps: {
        attributes: {
          dir,
          lang: dir === 'rtl' ? 'ar' : 'en',
          class: 'rte-content focus:outline-none',
        },
      },
      onUpdate: ({ editor: e }) => {
        onChangeRef.current(e.getHTML())
      },
    },
    // Empty deps: the editor is created once. Value/disabled/dir changes are
    // handled via the effects below so the editor instance stays stable.
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [],
  )

  // Sync value when it changes externally (locale switch, form reset, etc.).
  // Tiptap returns '<p></p>' for an empty doc, so treat it as empty string.
  useEffect(() => {
    const normalizedValue = value || ''
    const currentHTML = editor.getHTML()
    if (currentHTML !== normalizedValue && !(currentHTML === '<p></p>' && !normalizedValue)) {
      editor.commands.setContent(normalizedValue, { emitUpdate: false })
    }
  }, [value, editor])

  // Sync editable state.
  useEffect(() => {
    editor.setEditable(!disabled, false)
  }, [disabled, editor])

  // Sync dir / lang on the ProseMirror DOM node when locale tab changes.
  useEffect(() => {
    const dom = editor.view?.dom as HTMLElement | undefined
    if (!dom) return
    dom.setAttribute('dir', dir)
    dom.setAttribute('lang', dir === 'rtl' ? 'ar' : 'en')
  }, [dir, editor])

  return (
    <TooltipProvider>
      <div
        className={cn(
          'rte-container overflow-hidden rounded-md border border-input bg-transparent text-sm shadow-xs',
          'transition-[color,box-shadow] focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50',
          disabled && 'cursor-not-allowed opacity-50',
        )}
      >
        <Toolbar editor={editor} disabled={disabled} />
        <Separator />
        {/* Wrapper sets min-height; ProseMirror inherits it via CSS. */}
        <div style={{ minHeight }}>
          <EditorContent
            editor={editor}
            className="h-full [&_.rte-content]:min-h-[inherit] [&_.rte-content]:px-3 [&_.rte-content]:py-2"
          />
        </div>
        {maxLength !== undefined && <CharacterCountDisplay editor={editor} maxLength={maxLength} />}
      </div>
    </TooltipProvider>
  )
}
