// resources/js/Pages/Rooms/Show.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';

interface Plan {
    id: number;
    name: string;
    price_per_person: number;
    description: string;
}

interface Room {
    id: number;
    name: string;
    description: string;
    capacity: number;
    image_url: string;
    features: string[];
    plans: Plan[];
}

export default function Show({ room }: { room: Room }) {
    // フォームの状態管理
    const { data, setData, post, processing, errors } = useForm({
        plan_id: '',
        checkin_date: '',
        checkout_date: '',
        guest_count: 2,
    });

    const handleReserve = (planId: number) => {
        // プランIDをセットして送信（本来は確認画面へ行くのが理想ですが、まずは直接送信します）
        setData('plan_id', planId.toString());
        // 日付が入っているか簡易チェック
        if(!data.checkin_date) {
            alert('チェックイン日を選択してください');
            return;
        }
        post(route('reservations.store'));
    };

    return (
        <GuestLayout>
            <Head title={room.name} />

            <section className="pt-32 pb-20 max-w-7xl mx-auto px-4">
                <Link href="/rooms" className="text-stone-500 hover:text-stone-800 mb-8 inline-block text-sm">
                    ← お部屋一覧に戻る
                </Link>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-16">
                    {/* 左側：画像 */}
                    <div className="overflow-hidden shadow-xl">
                        <img src={room.image_url} alt={room.name} className="w-full h-auto object-cover" />
                    </div>

                    {/* 右側：情報 */}
                    <div className="flex flex-col justify-center">
                        <h1 className="text-4xl font-light tracking-widest mb-6">{room.name}</h1>
                        
                        <div className="flex gap-4 mb-8 text-sm text-stone-500">
                            <span>定員：{room.capacity}名様</span>
                            <div className="flex gap-2">
                                {room.features.map((feature, i) => (
                                    <span key={i} className="bg-stone-100 px-2 py-1">#{feature}</span>
                                ))}
                            </div>
                        </div>

                        <p className="text-stone-600 leading-loose mb-12 whitespace-pre-wrap">
                            {room.description}
                        </p>

                        {/* 予約アクション */}
                        <div className="border-t border-stone-200 pt-8 mt-8">
                            <Link
                                href={route('reservations.create', { room_id: room.id })}
                                className="block w-full text-center bg-stone-800 text-white py-4 tracking-widest hover:bg-stone-700 transition"
                            >
                                空室確認・予約に進む
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
