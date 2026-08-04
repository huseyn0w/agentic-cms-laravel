import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const setData = vi.fn();
vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...p }: any) => <a {...p}>{children}</a>,
  useForm: (initial: any) => ({ data: initial, errors: {}, processing: false, setData, post }),
}));
vi.mock('react-i18next', () => ({ useTranslation: () => ({ t: (k: string) => k }) }));
import Geo from './Geo';

const entity = {
  business_name: 'Elman Group', business_type: 'LocalBusiness', description: 'd',
  founder_name: '', services: 'A\nB', service_area: 'EU', contact_email: '',
  contact_phone: '', address: '', same_as: '', faq: '',
  emit_jsonld: true, include_in_llms: false,
};

describe('GEO settings', () => {
  it('prefills identity, type select and toggles, posts to the geo-settings endpoint', () => {
    render(<Geo geo_settings={entity} />);
    expect(screen.getByTestId('geo-business-name')).toHaveValue('Elman Group');
    expect(screen.getByLabelText('business_type')).toHaveValue('LocalBusiness');
    expect(screen.getByLabelText('emit_jsonld')).toBeChecked();
    expect(screen.getByLabelText('include_in_llms')).not.toBeChecked();

    fireEvent.submit(screen.getByTestId('geo-business-name').closest('form')!);
    expect(post).toHaveBeenCalledWith('/agentic-cms-laravel-admin/geo-settings', expect.anything());
  });
});
