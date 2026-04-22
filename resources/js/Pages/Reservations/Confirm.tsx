import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Confirm({ input, room, plan, totalPrice }: any) {
    const { post, processing } = useForm(input);
    return (
        <GuestLayout>
            <section className="pt-32 pb-20 max-w-xl mx-auto px-4 text-center">
                <h1 className="text-2xl mb-10">予約内容の最終確認</h1>
                <div className="bg-white p-8 border text-left space-y-4">
                    <p>お部屋：{room.name}</p>
                    <p>プラン：{plan.name}</p>
                    <div className="border-t pt-4 text-xl font-bold flex justify-between">
                        <span>合計料金</span>
                        <span className="text-amber-800">¥{totalPrice.toLocaleString()}</span>
                    </div>
                    <button onClick={() => post(route('reservations.store'))} className="w-full bg-amber-700 text-white py-4 mt-6">予約を確定する</button>
                </div>
            </section>
        </GuestLayout>
    );
}

