import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Thanks() {
    return (
        <GuestLayout>
            <Head title="お問い合わせ完了" />
            <section className="pt-40 pb-20 text-center">
                <div className="max-w-2xl mx-auto px-4">
                    <div className="mb-8">
                        <div className="inline-block bg-green-100 rounded-full p-4 mb-6">
                            <svg className="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <h1 className="text-4xl font-light mb-6 tracking-wide">お問い合わせありがとうございました。</h1>
                    <p className="mb-10 text-stone-600 leading-loose">
                        内容を確認のうえ、３営業日以内に担当者よりご連絡いたします。
                        連絡が無い場合は、お手数ですが再度お問い合わせください。
                    </p>
                    <Link
                        href="/"
                        className="inline-block bg-stone-800 text-white px-8 py-3 tracking-widest hover:bg-stone-700 transition-colors"
                    >
                        トップページへ戻る
                    </Link>
                </div>
            </section>
        </GuestLayout>
    );
}
