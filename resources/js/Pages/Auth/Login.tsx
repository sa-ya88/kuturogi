import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
    redirect,
}: {
    status?: string;
    canResetPassword: boolean;
    redirect?: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
        redirect: redirect ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="ログイン" />
            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">会員ログイン</h1>
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">登録済みのメールアドレスとパスワードでログインしてください</p>
                </div>
            </section>

            <section className="py-20 max-w-md mx-auto px-4">
                {status && (
                    <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 text-sm text-center">
                        {status}
                    </div>
                )}

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
                                className="mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                            />
                            {errors.email && (
                                <div className="text-red-600 text-sm mt-1">{errors.email}</div>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-sm font-medium text-stone-700">
                                パスワード
                            </label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                autoComplete="current-password"
                                required
                                onChange={(e) => setData('password', e.target.value)}
                                className="mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                            />
                            {errors.password && (
                                <div className="text-red-600 text-sm mt-1">{errors.password}</div>
                            )}
                        </div>

                        <div className="flex items-center justify-between">
                            <label className="flex items-center gap-2 text-sm text-stone-600 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="text-stone-800 focus:ring-amber-500 rounded border-stone-300"
                                />
                                ログイン状態を保持する
                            </label>

                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-xs text-stone-500 hover:text-amber-700 transition"
                                >
                                    パスワードをお忘れですか？
                                </Link>
                            )}
                        </div>

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-stone-800 text-white py-3 tracking-widest hover:bg-stone-700 transition disabled:opacity-50"
                            >
                                {processing ? 'ログイン中...' : 'ログイン'}
                            </button>
                        </div>
                    </form>

                    <div className="mt-8 pt-6 border-t border-stone-100 text-center text-sm text-stone-500">
                        アカウントをお持ちでない方は
                        <Link
                            href={route('register')}
                            className="text-amber-700 hover:text-amber-900 ml-1 transition"
                        >
                            新規会員登録
                        </Link>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
