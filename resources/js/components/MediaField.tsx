import { useLfmPicker } from '@/lib/lfm';
import { Button } from '@/components/Button';

export function MediaField({ value, onChange, label }: { value: string; onChange: (url: string) => void; label?: string }) {
  const { open } = useLfmPicker(onChange);

  return (
    <div className="flex flex-col gap-y-1.5">
      {label && <span className="font-sans font-medium text-sm text-fg">{label}</span>}
      <div className="admin-bevel rounded-lg p-3 flex items-center gap-3">
        {value ? (
          <img src={value} alt="Preview" className="h-16 w-16 rounded-md object-cover admin-sep border" />
        ) : (
          <div
            data-testid="media-field-placeholder"
            className="h-16 w-16 rounded-md admin-sep border flex items-center justify-center text-xs text-faint"
          >
            No image
          </div>
        )}
        <div className="flex items-center gap-2">
          <Button type="button" variant="outline" size="sm" onClick={() => open('Images')}>
            Choose image
          </Button>
          {value && (
            <Button type="button" variant="ghost" size="sm" onClick={() => onChange('')}>
              Remove
            </Button>
          )}
        </div>
      </div>
    </div>
  );
}
