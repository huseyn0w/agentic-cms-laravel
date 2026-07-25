import type { InputHTMLAttributes } from 'react';

interface TextFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    name: string;
    label?: string;
    error?: string | null;
    required?: boolean;
}

export function TextField({ name, label, error, required = false, className = '', ...rest }: TextFieldProps) {
    const hasError = Boolean(error);
    const descId = hasError ? `${name}-error` : undefined;

    return (
        <div className="flex flex-col gap-y-1.5">
            {label && (
                <label htmlFor={name} className="font-sans font-medium text-sm text-fg">
                    {label}
                    {required && (
                        <>
                            <span className="text-error ml-0.5" aria-hidden="true">*</span>
                            <span className="sr-only"> (required)</span>
                        </>
                    )}
                </label>
            )}
            <input
                id={name}
                name={name}
                required={required}
                aria-invalid={hasError || undefined}
                aria-describedby={descId}
                className={`field-input ${hasError ? 'border-[var(--error)]' : ''} ${className}`.trim()}
                {...rest}
            />
            {hasError && (
                <p id={descId} role="alert" className="text-xs text-error">
                    {error}
                </p>
            )}
        </div>
    );
}
