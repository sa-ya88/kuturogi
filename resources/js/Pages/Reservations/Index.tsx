import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

interface ReservationItem {
    id: number;
    checkin_date: string;
    checkout_date: string;
    guest_count: number;
    total_price: number;
    status: string;
    plan: { name: string };
    room: { name: string };
}

export default function Index({ reservations }: { reservations: ReservationItem[] }) {
    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const formatPrice = (price: number) => {
        return price.toLocaleString('ja-JP');
    };

    const statusLabel = (status: string) => {
        switch (status) {
            case 'confirmed':
                return '確定';
            case 'pending':
                return '確認中';
            case 'cancelled':
                return 'キャンセル';
            default:
                return status;
        }
    };

    return (
        <GuestLayout>
            <Head title="予約確認" />
            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">予約確認</h1>
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">ご予約内容の一覧です</p>
                </div>
            </section>

            <section className="py-16 max-w-4xl mx-auto px-4">
                {reservations.length === 0 ? (
                    <div className="bg-white border border-stone-200 p-12 text-center">
                        <p className="text-stone-600 mb-8">現在、ご予約はありません。</p>
                        <Link
                            href={route('reservations.create')}
                            className="inline-block bg-stone-800 text-white px-8 py-3 tracking-widest hover:bg-stone-700 transition"
                        >
                            宿泊プランを見る
                        </Link>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {reservations.map((reservation) => (
                            <div
                                key={reservation.id}
                                className="bg-white border border-stone-200 p-6 md:p-8"
                            >
                                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-stone-100 pb-4 mb-4">
                                    <div>
                                        <p className="text-xs text-stone-500 tracking-widest mb-1">
                                            予約番号 #{reservation.id}
                                        </p>
                                        <h2 className="text-lg font-bold tracking-wide">
                                            {reservation.room.name} / {reservation.plan.name}
                                        </h2>
                                    </div>
                                    <span className="inline-block text-xs tracking-widest border border-stone-300 px-3 py-1 self-start">
                                        {statusLabel(reservation.status)}
                                    </span>
                                </div>

                                <dl className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt className="text-stone-500 mb-1">チェックイン</dt>
                                        <dd>{formatDate(reservation.checkin_date)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-stone-500 mb-1">チェックアウト</dt>
                                        <dd>{formatDate(reservation.checkout_date)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-stone-500 mb-1">宿泊人数</dt>
                                        <dd>{reservation.guest_count}名</dd>
                                    </div>
                                    <div>
                                        <dt className="text-stone-500 mb-1">合計金額</dt>
                                        <dd>¥{formatPrice(reservation.total_price)}</dd>
                                    </div>
                                </dl>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </GuestLayout>
    );
}
