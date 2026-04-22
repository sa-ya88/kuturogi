// resources/js/Pages/Company.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

export default function Company() {
    const info = [
        { label: '屋号', value: 'くつろぎの宿' },
        { label: '会社名', value: '株式会社くつろぎリゾート' },
        { label: '所在地', value: '〇〇県〇〇市〇〇町1-2-3' },
        { label: '創業', value: '大正12年' },
    ];

    return (
        <GuestLayout>
            <Head title="企業情報" />
            <section className="pt-32 pb-16 bg-stone-100 text-center">
                <h1 className="text-4xl font-light tracking-widest">企業情報</h1>
            </section>
            <section className="py-20 max-w-2xl mx-auto px-4">
                <dl className="space-y-4">
                    {info.map((item, i) => (
                        <div key={i} className="flex border-b border-stone-100 py-4">
                            <dt className="w-1/3 font-bold">{item.label}</dt>
                            <dd className="w-2/3">{item.value}</dd>
                        </div>
                    ))}
                </dl>
            </section>
        </GuestLayout>
    );
}
