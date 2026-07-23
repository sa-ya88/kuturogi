import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Thanks() {
    return (
        <GuestLayout>
            <Head title="予約完了" />
            <section className="pt-40 pb-20 text-center">
                <div className="max-w-2xl mx-auto px-4">
                    <div className="mb-8">
                        <div className="inline-block bg-green-100 rounded-full p-4 mb-6">
                            <svg className="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <h1 className="text-4xl font-light mb-6 tracking-wide">ご予約ありがとうございました</h1>
                    <p className="mb-4 text-stone-600 leading-loose">
                        ご予約が完了いたしました。<br />
                        確認メールをお送りいたしましたので、ご確認ください。
                    </p>
                    <p className="mb-10 text-sm text-stone-500">
                        予約内容についてご質問がございましたら、<br />
                        お気軽にお問い合わせください。
                    </p>
                    <div className="space-y-4">
                        <Link 
                            href="/" 
                            className="inline-block bg-stone-800 text-white px-8 py-3 tracking-widest hover:bg-stone-700 transition-colors"
                        >
                            トップページへ戻る
                        </Link>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
