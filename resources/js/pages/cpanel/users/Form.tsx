import { Head, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminLayout } from '@/layouts/AdminLayout';
import { TextField } from '@/components/TextField';
import { Button } from '@/components/Button';
import { MediaField } from '@/components/MediaField';
import type { SharedProps } from '@/lib/types';
import type { FormEvent, ReactElement } from 'react';

interface UserEntity {
  id: number;
  username: string;
  email: string;
  name: string;
  surname: string;
  country: string;
  city: string;
  about_me: string;
  facebook_url: string;
  twitter_url: string;
  instagram_url: string;
  google_url: string;
  linkedin_url: string;
  xing_url: string;
  role_id: number;
  gender: string;
  avatar: string;
}
interface Country {
  name: string;
}
interface Role {
  id: number;
  name: string;
}
interface FormProps {
  entity: UserEntity | null;
  countries: Country[];
  user_roles: Role[];
}

const BASE = '/agentic-cms-laravel-admin/users';

// [form field, i18n key suffix, English fallback]. The lang file keys the
// social labels without the `_url` suffix (facebook, google, ...).
const SOCIALS = [
  ['facebook_url', 'facebook', 'Facebook'],
  ['google_url', 'google', 'Google'],
  ['twitter_url', 'twitter', 'Twitter'],
  ['instagram_url', 'instagram', 'Instagram'],
  ['linkedin_url', 'linkedin', 'LinkedIn'],
  ['xing_url', 'xing', 'Xing'],
] as const;

export default function Form({ entity, countries, user_roles }: FormProps) {
  const { t } = useTranslation();
  const tr = (k: string, f: string) => (t(k) === k ? f : t(k));
  const canManageUsers = usePage<SharedProps>().props.auth.can.manage_users;

  const form = useForm({
    username: entity?.username ?? '',
    email: entity?.email ?? '',
    password: '',
    password_confirmation: '',
    name: entity?.name ?? '',
    surname: entity?.surname ?? '',
    country: entity?.country ?? (countries[0]?.name ?? ''),
    city: entity?.city ?? '',
    role_id: entity?.role_id ?? (user_roles[0]?.id ?? 0),
    about_me: entity?.about_me ?? '',
    gender: entity?.gender ?? '',
    facebook_url: entity?.facebook_url ?? '',
    google_url: entity?.google_url ?? '',
    twitter_url: entity?.twitter_url ?? '',
    instagram_url: entity?.instagram_url ?? '',
    linkedin_url: entity?.linkedin_url ?? '',
    xing_url: entity?.xing_url ?? '',
    avatar: entity?.avatar ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (entity) form.put(`${BASE}/${entity.id}/update`);
    else form.post(`${BASE}/new`);
  };

  const heading = entity
    ? tr('cpanel/users.profile_headline', 'Edit profile')
    : tr('cpanel/users.new_user_headline', 'New user');

  return (
    <>
      <Head title={heading} />
      <form onSubmit={submit}>
        <div className="mb-5 flex items-center gap-4">
          <h1 className="text-[22px] font-semibold tracking-tight">{heading}</h1>
          <div className="ml-auto flex items-center gap-2.5">
            <Button href={BASE} variant="outline" size="md">{tr('cpanel/users.cancel', 'Cancel')}</Button>
            <Button type="submit" variant="primary" size="md" loading={form.processing} data-testid="user-submit">
              {tr('cpanel/users.save', 'Save')}
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_320px]">
          <section className="admin-card flex flex-col gap-4 p-[18px]">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              {entity ? (
                <div className="flex flex-col gap-y-1.5">
                  <span className="font-sans text-sm font-medium text-fg">{tr('cpanel/users.username', 'Username')}</span>
                  <p className="rounded-lg bg-surface-2 px-3.5 py-2.5 text-sm font-medium text-fg">{entity.username}</p>
                </div>
              ) : (
                <TextField name="username" label={tr('cpanel/users.username', 'Username')} required
                  data-testid="user-username" value={form.data.username} error={form.errors.username}
                  onChange={(e) => form.setData('username', e.target.value)} />
              )}
              <TextField name="email" type="email" label={tr('cpanel/users.email', 'Email')} required
                data-testid="user-email" value={form.data.email} error={form.errors.email}
                onChange={(e) => form.setData('email', e.target.value)} />
              <TextField name="password" type="password" label={tr('cpanel/users.new_password', 'Password')}
                autoComplete="new-password" value={form.data.password} error={form.errors.password}
                onChange={(e) => form.setData('password', e.target.value)} />
              <TextField name="password_confirmation" type="password"
                label={tr('cpanel/users.new_password_confirmation', 'Confirm password')}
                autoComplete="new-password" value={form.data.password_confirmation}
                onChange={(e) => form.setData('password_confirmation', e.target.value)} />
              <TextField name="name" label={tr('cpanel/users.name', 'Name')}
                value={form.data.name} error={form.errors.name}
                onChange={(e) => form.setData('name', e.target.value)} />
              <TextField name="surname" label={tr('cpanel/users.surname', 'Surname')}
                value={form.data.surname} error={form.errors.surname}
                onChange={(e) => form.setData('surname', e.target.value)} />
              <div className="flex flex-col gap-y-1.5">
                <label htmlFor="country" className="font-sans text-sm font-medium text-fg">
                  {tr('cpanel/users.country', 'Country')}
                </label>
                <select id="country" name="country" aria-label="user-country" className="field-input w-full"
                  value={form.data.country} onChange={(e) => form.setData('country', e.target.value)}>
                  {countries.map((c) => (
                    <option key={c.name} value={c.name}>{c.name}</option>
                  ))}
                </select>
              </div>
              <TextField name="city" label={tr('cpanel/users.city', 'City')}
                value={form.data.city} error={form.errors.city}
                onChange={(e) => form.setData('city', e.target.value)} />
            </div>

            {canManageUsers && (
              <div className="flex flex-col gap-y-1.5">
                <label htmlFor="role_id" className="font-sans text-sm font-medium text-fg">
                  {tr('cpanel/users.status', 'Role')}
                </label>
                <select id="role_id" name="role_id" aria-label="user-role" className="field-input w-full"
                  value={form.data.role_id} onChange={(e) => form.setData('role_id', Number(e.target.value))}>
                  {user_roles.map((r) => (
                    <option key={r.id} value={r.id}>{r.name}</option>
                  ))}
                </select>
              </div>
            )}

            <div className="flex flex-col gap-y-1.5">
              <label htmlFor="about_me" className="font-sans text-sm font-medium text-fg">
                {tr('cpanel/users.about', 'About')}
              </label>
              <textarea id="about_me" name="about_me" rows={4} className="field-input w-full"
                value={form.data.about_me} onChange={(e) => form.setData('about_me', e.target.value)} />
            </div>

            <div className="mt-2 border-t admin-sep pt-4">
              <h3 className="mb-3 text-[13px] font-semibold">{tr('cpanel/users.social_profiles', 'Social profiles')}</h3>
              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                {SOCIALS.map(([field, key, label]) => (
                  <TextField key={field} name={field} label={tr(`cpanel/users.${key}`, label)} placeholder="https://"
                    value={form.data[field]} error={form.errors[field]}
                    onChange={(e) => form.setData(field, e.target.value)} />
                ))}
              </div>
            </div>
          </section>

          <section className="admin-card flex flex-col gap-4 p-[18px]">
            <div className="flex flex-col gap-y-1.5">
              <span className="font-sans text-sm font-medium text-fg">{tr('cpanel/users.gender', 'Gender')}</span>
              <div className="flex flex-wrap gap-6">
                <label className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                  <input type="radio" name="gender" value="male" checked={form.data.gender === 'male'}
                    onChange={(e) => form.setData('gender', e.target.value)} />
                  {tr('cpanel/users.gender_male', 'Male')}
                </label>
                <label className="flex cursor-pointer items-center gap-2.5 text-sm text-fg">
                  <input type="radio" name="gender" value="female" checked={form.data.gender === 'female'}
                    onChange={(e) => form.setData('gender', e.target.value)} />
                  {tr('cpanel/users.gender_female', 'Female')}
                </label>
              </div>
            </div>

            <MediaField label={tr('cpanel/users.avatar', 'Avatar')}
              value={form.data.avatar} onChange={(url) => form.setData('avatar', url)} />
          </section>
        </div>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement) => (
  <AdminLayout breadcrumb="Admin / Users / Edit">{page}</AdminLayout>
);
