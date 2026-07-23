// resources/js/Pages/Reservations/Confirm.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Confirm({ input, room, plan, totalPrice, pricePerPersonPerNight, nights }: any) {
    const { post, processing } = useForm(input);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('reservations.store'));
    };

    return (
        <GuestLayout>
            <Head title="予約内容の確認" />
            <section className="pt-32 pb-20 max-w-6xl mx-auto px-4">
                <h1 className="text-2xl font-light tracking-widest text-center mb-12 text-stone-800">ご予約内容の最終確認</h1>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    {/* 左側：予約内容 */}
                    <div className="lg:col-span-2">
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
                                        {input.check_in_date || input.checkin_date || '未選択（後で入力してください）'}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-[10px] text-stone-400 block mb-1">チェックアウト</label>
                                    <p className="font-medium">{input.check_out_date || input.checkout_date}</p>
                                </div>
                            </div>
                            <div className="border-b pb-4">
                                <label className="text-[10px] text-stone-400 block mb-1">宿泊人数</label>
                                <p className="font-medium">大人 {input.adult_count}名 / 子供 {input.child_count}名（計 {input.room_count}室）</p>
                            </div>

                            <div className="border-b pb-4">
                                <label className="text-[10px] text-stone-400 block mb-1">お客様情報</label>
                                <div className="space-y-2 text-sm">
                                    <p><span className="text-stone-500">お名前：</span> {input.last_name} {input.first_name}</p>
                                    <p><span className="text-stone-500">フリガナ：</span> {input.last_name_kana} {input.first_name_kana}</p>
                                    <p><span className="text-stone-500">電話番号：</span> {input.tel}</p>
                                    <p><span className="text-stone-500">メール：</span> {input.email}</p>
                                    <p><span className="text-stone-500">住所：</span> {input.zip_code} {input.address} {input.building}</p>
                                </div>
                            </div>

                            <div className="pb-4">
                                <label className="text-[10px] text-stone-400 block mb-1">お支払い方法</label>
                                <p className="font-medium text-amber-700">
                                    {input.payment_method === 'local' ? '現地決済' : 'クレジットカード（オンライン決済）'}
                                </p>
                            </div>

                            <form onSubmit={submit} className="pt-6">
                                <button 
                                    disabled={processing}
                                    className="w-full bg-stone-800 text-white py-4 tracking-widest hover:bg-stone-700 transition font-bold disabled:opacity-50"
                                >
                                    {processing ? '処理中...' : 'この内容で予約を確定する'}
                                </button>
                                <button type="button" onClick={() => window.history.back()} className="w-full mt-4 text-sm text-stone-400 hover:text-stone-600">
                                    入力内容を修正する
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* 右側：料金内訳 */}
                    <div className="h-fit">
                        <div className="bg-stone-800 text-white p-6 shadow-xl rounded-lg">
                            <h3 className="text-lg font-bold mb-6 border-b border-stone-600 pb-2 tracking-widest">料金内訳</h3>
                            <div className="space-y-4 text-sm mb-8">
                                <div className="flex justify-between">
                                    <span>大人 (¥{pricePerPersonPerNight?.toLocaleString()} × {input.adult_count}名 × {nights}泊)</span>
                                    <span>¥{(pricePerPersonPerNight * input.adult_count * nights).toLocaleString()}</span>
                                </div>
                                {input.child_count > 0 && (
                                    <div className="flex justify-between">
                                        <span>子供 (¥{(pricePerPersonPerNight * 0.7).toLocaleString()} × {input.child_count}名 × {nights}泊)</span>
                                        <span>¥{(pricePerPersonPerNight * 0.7 * input.child_count * nights).toLocaleString()}</span>
                                    </div>
                                )}
                                <div className="flex justify-between opacity-60 text-xs">
                                    <span>部屋数</span>
                                    <span>× {input.room_count}室</span>
                                </div>
                                <div className="border-t border-stone-600 pt-4 flex justify-between items-end">
                                    <span className="text-xs">合計金額 (税込)</span>
                                    <span className="text-2xl font-serif text-amber-400">¥{totalPrice?.toLocaleString()}</span>
                                </div>
                            </div>
                            <div className="bg-stone-700 p-4 rounded text-xs text-stone-300 space-y-2">
                                <p className="font-bold text-white mb-2">ご注意</p>
                                <p>• 入湯税（150円/人）は現地でお支払いください</p>
                                <p>• キャンセル料が発生する場合があります</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
