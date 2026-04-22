// resources/js/Pages/Contact.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Contact() {
    // フォームの状態管理
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        subject: '宿泊について',
        message: '',
    });

    // 送信処理
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('contact.send'), {
            onSuccess: () => {
                alert('お問い合わせを受け付けました。');
                reset();
            },
        });
    };

    return (
        <GuestLayout>
            <Head title="お問い合わせ" />

            <section className="pt-32 pb-16 bg-stone-100 text-center">
                <h1 className="text-4xl font-light tracking-widest">お問い合わせ</h1>
                <p className="mt-4 text-stone-500 text-sm">ご不明な点など、お気軽にお尋ねください。</p>
            </section>

            <section className="py-20 max-w-2xl mx-auto px-4">
                <form onSubmit={submit} className="space-y-6">
                    {/* お名前 */}
                    <div>
                        <label className="block text-sm font-medium text-stone-700">お名前</label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                            required
                        />
                        {errors.name && <div className="text-red-600 text-sm mt-1">{errors.name}</div>}
                    </div>

                    {/* メールアドレス */}
                    <div>
                        <label className="block text-sm font-medium text-stone-700">メールアドレス</label>
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                            required
                        />
                        {errors.email && <div className="text-red-600 text-sm mt-1">{errors.email}</div>}
                    </div>

                    {/* 件名 */}
                    <div>
                        <label className="block text-sm font-medium text-stone-700">お問い合わせ項目</label>
                        <select
                            value={data.subject}
                            onChange={(e) => setData('subject', e.target.value)}
                            className="mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option>宿泊について</option>
                            <option>お料理について</option>
                            <option>団体予約・宴会について</option>
                            <option>その他</option>
                        </select>
                    </div>

                    {/* 内容 */}
                    <div>
                        <label className="block text-sm font-medium text-stone-700">お問い合わせ内容</label>
                        <textarea
                            rows={5}
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                            className="mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                            required
                        ></textarea>
                        {errors.message && <div className="text-red-600 text-sm mt-1">{errors.message}</div>}
                    </div>

                    <div className="text-center">
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-stone-800 text-white px-12 py-3 tracking-widest hover:bg-stone-700 transition disabled:opacity-50"
                        >
                            {processing ? '送信中...' : '内容を送信する'}
                        </button>
                    </div>
                </form>
            </section>
        </GuestLayout>
    );
}
