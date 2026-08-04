import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import type { FormEvent, ReactElement } from 'react';

interface RoleEntity {
  id: number;
  name: string;
  permissions: string[];
}
interface PermissionOption {
  name: string;
  label: string;
}
interface FormProps {
  entity: RoleEntity | null;
  permission_options: PermissionOption[];
}

const BASE = '/agentic-cms-laravel-admin/roles';

export default function Form({ entity, permission_options }: FormProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));

  const form = useForm<{ name: string; permissions: string[] }>({
    name: entity?.name ?? '',
    permissions: entity?.permissions ?? [],
  });

  const togglePermission = (name: string) => {
    const next = form.data.permissions.includes(name)
      ? form.data.permissions.filter((p) => p !== name)
      : [...form.data.permissions, name];
    form.setData('permissions', next);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (entity) form.put(`${BASE}/${entity.id}/update`);
    else form.post(`${BASE}/new`);
  };

  const heading = entity
    ? tr('cpanel/roles.edit_role_headline', 'Edit role')
    : tr('cpanel/roles.new_role_headline', 'New role');

  return (
    <>
      <Head title={heading} />
      <form onSubmit={submit} className="mx-auto max-w-3xl">
        <div className="mb-5 flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">{heading}</h1>
          <div className="ml-auto flex items-center gap-2.5">
            <Button href={BASE} variant="outline" size="md">{tr('cpanel/roles.cancel', 'Cancel')}</Button>
            <Button type="submit" variant="primary" size="md" loading={form.processing} data-testid="role-submit">
              {tr('cpanel/roles.save', 'Save')}
            </Button>
          </div>
        </div>

        <section className="admin-card flex flex-col gap-4 p-[18px]">
          <TextField name="name" label={tr('cpanel/roles.role_name', 'Role name')} required
            data-testid="role-name" value={form.data.name} error={form.errors.name}
            onChange={(e) => form.setData('name', e.target.value)} />

          <fieldset className="rounded-lg border admin-sep p-4">
            <legend className="px-1 text-[11px] font-semibold uppercase tracking-wide text-muted">
              {tr('cpanel/roles.table_action', 'Permissions')}
            </legend>
            <div className="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
              {permission_options.map((p) => (
                <label key={p.name} htmlFor={p.name}
                  className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                  <input type="checkbox" id={p.name} aria-label={`perm-${p.name}`}
                    checked={form.data.permissions.includes(p.name)}
                    onChange={() => togglePermission(p.name)} />
                  <span className="capitalize">{p.label}</span>
                </label>
              ))}
            </div>
          </fieldset>
        </section>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Roles / Edit">{page}</AdminLayout>
);
