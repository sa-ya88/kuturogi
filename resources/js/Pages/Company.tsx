import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

export default function Company() {
    const info = [
        { label: '屋号', value: 'くつろぎの宿' },
        { label: '会社名', value: '株式会社くつろぎリゾート' },
        {
            label: '所在地',
            value: '〒000-0000\n静岡県隠れ里郡山奥町字緑渓谷 108-5'
        },
        { label: 'TEL', value: '011-000-0000' },
        { label: 'FAX', value: '011-000-0000' },
        { label: '代表者', value: '山田 太郎' },
        { label: '資本金', value: '1,000万円' },
        { label: '創業', value: '大正12年' },
    ];

    return (
        <GuestLayout>
            <Head title="企業情報" />
            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">企業情報</h1>
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">すべて架空の情報です。</p>
                </div>
            </section>

            <section className="py-20 max-w-3xl mx-auto px-4">
                <div className="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                    <table className="w-full text-left border-collapse">
                        <tbody>
                            {info.map((item, i) => (
                                <tr
                                    key={i}
                                    className="border-b border-stone-100 last:border-none transition-colors hover:bg-stone-50/30"
                                >
                                    <th className="w-1/3 px-6 py-5 font-medium text-stone-800 bg-stone-50/50 align-top border-r border-stone-100 select-none">
                                        {item.label}
                                    </th>
                                    <td className="w-2/3 px-6 py-5 text-stone-600 align-top whitespace-pre-line leading-relaxed">
                                        {item.value}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>
        </GuestLayout>
    );
}
