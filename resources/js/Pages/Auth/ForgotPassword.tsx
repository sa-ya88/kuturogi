import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    const inputClassName =
        'mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500';

    return (
        <GuestLayout>
            <Head title="パスワードをお忘れの方" />
            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">パスワード再設定</h1>
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">パスワードをお忘れの場合は、ご登録のメールアドレスを入力してください</p>
                </div>
            </section>

            <section className="py-20 max-w-md mx-auto px-4">
                <div className="bg-white border border-stone-200 p-8 shadow-sm space-y-6">
                    
                    <p className="text-stone-600 text-sm leading-relaxed font-serif">
                        ご入力いただいたメールアドレス宛に、パスワードの再設定用URLを記載したメールをお送りいたします。メールの案内手順に沿って、新しいパスワードを設定してください。
                    </p>

                    {/* 送信完了時のメッセージ表示 */}
                    {status && (
                        <div className="p-4 bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm text-center rounded tracking-wider">
                            {status}
                        </div>
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
                                autoFocus
                                required
                                onChange={(e) => setData('email', e.target.value)}
                                className={inputClassName}
                                placeholder="example@email.com"
                            />
                            {errors.email && (
                                <div className="text-red-600 text-sm mt-1">{errors.email}</div>
                            )}
                        </div>

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-stone-800 text-white py-3 tracking-widest hover:bg-stone-700 transition disabled:opacity-50 font-serif"
                            >
                                {processing ? '送信中...' : '再設定用メールを送信する'}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </GuestLayout>
    );
}
