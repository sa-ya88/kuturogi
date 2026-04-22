// resources/js/Pages/Faq.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

const faqs = [
    { q: 'チェックインは何時までですか？', a: '最終チェックインは19:00となっております。遅れる場合はご連絡ください。' },
    { q: '食物アレルギーの対応は可能ですか？', a: 'はい、事前にご相談いただければ可能な限り対応させていただきます。' },
];

export default function Faq() {
    return (
        <GuestLayout>
            <Head title="よくある質問" />
            <section className="pt-32 pb-16 bg-stone-100 text-center">
                <h1 className="text-4xl font-light tracking-widest">よくある質問</h1>
            </section>
            <section className="py-20 max-w-3xl mx-auto px-4 space-y-8">
                {faqs.map((faq, i) => (
                    <div key={i} className="border-b border-stone-200 pb-6">
                        <h2 className="text-lg font-medium mb-2">Q. {faq.q}</h2>
                        <p className="text-stone-600 pl-6">A. {faq.a}</p>
                    </div>
                ))}
            </section>
        </GuestLayout>
    );
}
