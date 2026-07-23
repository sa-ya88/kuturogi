import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

export default function RegisterSent() {
    return (
        <GuestLayout>
            <Head title="認証メール送信完了" />
            <section className="pt-32 pb-16 bg-stone-100 text-center">
                <h1 className="text-4xl font-light tracking-widest text-stone-800">メール送信完了</h1>
                <p className="mt-4 text-stone-500 text-sm">
                    アカウント作成のための案内メールをお送りしました
                </p>
            </section>

            <section className="py-20 max-w-md mx-auto px-4">
                <div className="bg-white border border-stone-200 p-8 shadow-sm text-center space-y-6">
                    <div className="text-stone-700 text-sm leading-relaxed text-left">
                        <p className="font-medium text-center text-base mb-2">まだ登録は完了していません</p>
                        <p className="text-stone-500 text-center">
                            入力されたメールアドレス宛に、登録用URLを記載した認証メールを送信しました。<br />
                            メールの本文にあるリンクをクリックして、プロフィールの登録を完了させてください。
                        </p>
                    </div>
                    <div className="pt-4 border-t border-stone-100">
                        <Link
                            href={route('login')}
                            className="inline-block text-sm text-amber-700 hover:text-amber-900 transition"
                        >
                            ログイン画面へ戻る
                        </Link>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
