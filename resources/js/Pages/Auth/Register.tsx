import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Register() {
    // 1. 入力項目をemailのみに設定
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        // 2. Inertiaのpostを使って、auth.phpに登録した名前付きルート「register.email」へ送信
        post(route('register.email'));
    };

    const inputClassName =
        'mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500';

    return (
        <GuestLayout>
            <Head title="新規会員登録" />
            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">新規会員登録</h1>
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">メールアドレスをご入力いただくと、登録用URLを記載した認証メールをお送りします</p>
                </div>
            </section>

            <section className="py-20 max-w-md mx-auto px-4">
                <div className="bg-white border border-stone-200 p-8 shadow-sm">
                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <label htmlFor="email" className="block text-sm font-medium text-stone-700">
                                メールアドレス
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                autoComplete="username"
                                autoFocus
                                required
                                onChange={(e) => setData('email', e.target.value)}
                                className={inputClassName}
                            />
                            {errors.email && (
                                <div className="text-red-600 text-sm mt-1">{errors.email}</div>
                            )}
                        </div>

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-stone-800 text-white py-3 tracking-widest hover:bg-stone-700 transition disabled:opacity-50"
                            >
                                {processing ? '送信中...' : '認証メールを送信する'}
                            </button>
                        </div>
                    </form>

                    <div className="mt-8 pt-6 border-t border-stone-100 text-center text-sm text-stone-500">
                        すでにアカウントをお持ちの方は
                        <Link
                            href={route('login')}
                            className="text-amber-700 hover:text-amber-900 ml-1 transition"
                        >
                            ログイン
                        </Link>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
