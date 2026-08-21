import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface ReservationItem {
    id: number;
    checkin_date: string;
    checkout_date: string;
    guest_count: number;
    total_price: number;
    status: string;
    can_cancel_without_fee: boolean;
    plan: { name: string };
    room: { name: string };
}

export default function Index({
    reservations,
    cancelPolicy = [],
}: {
    reservations: ReservationItem[];
    cancelPolicy?: string[];
}) {
    const { flash, errors } = usePage().props;
    const policyLines = cancelPolicy.length > 0 ? cancelPolicy : ['キャンセルポリシーは準備中です'];

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
                {flash?.success && (
                    <p className="mb-6 text-sm text-green-700 bg-green-50 border border-green-100 px-4 py-3">
                        {flash.success}
                    </p>
                )}
                {errors.cancel && (
                    <p className="mb-6 text-sm text-red-700 bg-red-50 border border-red-100 px-4 py-3">
                        {errors.cancel}
                    </p>
                )}

                <div className="bg-stone-50 border border-stone-200 p-6 md:p-8 mb-8">
                    <h2 className="text-sm font-bold tracking-widest mb-4">キャンセルポリシー</h2>
                    <div className="space-y-1 text-sm text-red-600 leading-loose">
                        {policyLines.map((line) => (
                            <p key={line}>{line}</p>
                        ))}
                    </div>
                </div>

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

                                {reservation.status !== 'cancelled' && (
                                    <div className="mt-6 pt-4 border-t border-stone-100">
                                        {reservation.can_cancel_without_fee ? (
                                            <CancelButton reservationId={reservation.id} />
                                        ) : (
                                            <p className="text-sm text-stone-600">
                                                キャンセルする場合はお電話ください
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </GuestLayout>
    );
}

function CancelButton({ reservationId }: { reservationId: number }) {
    const [processing, setProcessing] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!window.confirm('この予約をキャンセルしますか？')) {
            return;
        }

        setProcessing(true);
        router.post(route('reservations.cancel', reservationId), {}, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <form onSubmit={submit}>
            <button
                type="submit"
                disabled={processing}
                className="inline-block border border-stone-800 text-stone-800 px-6 py-2 text-sm tracking-widest hover:bg-stone-800 hover:text-white transition disabled:opacity-50"
            >
                {processing ? '処理中…' : '予約をキャンセル'}
            </button>
        </form>
    );
}
