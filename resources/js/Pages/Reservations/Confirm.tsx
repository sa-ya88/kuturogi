// resources/js/Pages/Reservations/Confirm.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Confirm({ input, room, plan }: any) {
    const { post, processing } = useForm(input);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('reservations.store'));
    };

    return (
        <GuestLayout>
            <Head title="予約内容の確認" />
            <section className="pt-32 pb-20 max-w-2xl mx-auto px-4">
                <h1 className="text-2xl font-light tracking-widest text-center mb-10 text-stone-800">ご予約内容の最終確認</h1>

                <div className="bg-white p-8 border border-stone-200 shadow-sm space-y-6">
                    <div className="border-b pb-4">
                        <label className="text-[10px] text-stone-400 block mb-1">お部屋・プラン</label>
                        <p className="font-bold">{room.name}</p>
                        <p className="text-sm text-stone-600">{plan.name}</p>
                    </div>
                    <div className="grid grid-cols-2 gap-6 border-b pb-4">
                        <div>
                            <label className="text-[10px] text-stone-400 block mb-1">チェックイン</label>
                            <p className="font-medium text-amber-700">
                                {input.checkin_date || '未選択（後で入力してください）'}
                            </p>
                        </div>
                        <div>
                            <label className="text-[10px] text-stone-400 block mb-1">チェックアウト</label>
                            <p className="font-medium">{input.checkout_date}</p>
                        </div>
                    </div>
                    <div className="border-b pb-4">
                        <label className="text-[10px] text-stone-400 block mb-1">宿泊人数</label>
                        <p className="font-medium">大人 {input.adult_count}名 / 子供 {input.child_count}名（計 {input.room_count}室）</p>
                    </div>

                    <form onSubmit={submit} className="pt-6">
                        <button 
                            disabled={processing}
                            className="w-full bg-stone-800 text-white py-4 tracking-widest hover:bg-stone-700 transition font-bold"
                        >
                            {processing ? '処理中...' : 'この内容で予約を確定する'}
                        </button>
                        <button type="button" onClick={() => window.history.back()} className="w-full mt-4 text-sm text-stone-400 hover:text-stone-600">
                            入力内容を修正する
                        </button>
                    </form>
                </div>
            </section>
        </GuestLayout>
    );
}
