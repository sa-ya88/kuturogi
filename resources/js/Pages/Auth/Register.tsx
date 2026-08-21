import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import DemoNotice from '@/Components/DemoNotice';
import { PageProps } from '@/types';

export default function Register() {
    const demo = usePage<PageProps>().props.demo;
    const demoEnabled = demo?.enabled;
    const registrationClosed = demoEnabled && demo?.allowRegistration === false;
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });
    const guestForm = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (registrationClosed) {
            return;
        }

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
                    {demoEnabled && (
                        <>
                            <DemoNotice />
                            {registrationClosed && (
                                <p className="mb-6 text-sm text-stone-600 leading-relaxed">
                                    公開デモでは新規会員登録を停止しています。画面の見た目は確認できますが、認証メールは送信されません。ゲスト（テストユーザー）でログインしてください。
                                </p>
                            )}
                            <button
                                type="button"
                                disabled={guestForm.processing}
                                onClick={() => guestForm.post(route('guest.login'))}
                                className="mb-6 w-full border border-stone-800 text-stone-800 py-3 tracking-widest hover:bg-stone-50 transition disabled:opacity-50"
                            >
                                {guestForm.processing ? 'ログイン中...' : 'ゲスト（テストユーザー）でログイン'}
                            </button>
                        </>
                    )}
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
                                disabled={registrationClosed}
                                onChange={(e) => setData('email', e.target.value)}
                                className={`${inputClassName} disabled:bg-stone-100 disabled:text-stone-400`}
                            />
                            {errors.email && (
                                <div className="text-red-600 text-sm mt-1">{errors.email}</div>
                            )}
                        </div>

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={processing || registrationClosed}
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
