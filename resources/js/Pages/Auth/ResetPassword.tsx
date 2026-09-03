import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const inputClassName =
        'mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500';

    return (
        <GuestLayout>
            <Head title="パスワードの再設定" />

            <section className="pt-32 pb-16 bg-stone-100 text-center">
                <h1 className="text-4xl font-light tracking-widest text-stone-800">新しいパスワードの設定</h1>
                <p className="mt-4 text-stone-500 text-sm font-serif">
                    新しく設定するパスワードをご入力ください
                </p>
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
                                required
                                readOnly
                                className="mt-1 block w-full border-stone-200 rounded-md bg-stone-50 text-stone-500 cursor-not-allowed shadow-sm focus:ring-0 focus:border-stone-200"
                            />
                            {errors.email && (
                                <div className="text-red-600 text-sm mt-1">{errors.email}</div>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-sm font-medium text-stone-700">
                                新しいパスワード
                            </label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                autoFocus
                                required
                                onChange={(e) => setData('password', e.target.value)}
                                className={inputClassName}
                            />
                            {errors.password && (
                                <div className="text-red-600 text-sm mt-1">{errors.password}</div>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="block text-sm font-medium text-stone-700">
                                新しいパスワード（確認）
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                value={data.password_confirmation}
                                required
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className={inputClassName}
                            />
                            {errors.password_confirmation && (
                                <div className="text-red-600 text-sm mt-1">{errors.password_confirmation}</div>
                            )}
                        </div>

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-stone-800 text-white py-3 tracking-widest hover:bg-stone-700 transition disabled:opacity-50 font-serif"
                            >
                                {processing ? '変更中...' : 'パスワードを更新する'}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </GuestLayout>
    );
}
