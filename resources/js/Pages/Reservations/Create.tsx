import GuestLayout from '@/Layouts/GuestLayout';
import StayCalendarModal from '@/Components/StayCalendarModal';
import { Head, router, useForm } from '@inertiajs/react';
import { useState, useMemo, useRef } from 'react';

const ISO_DATE = /^(\d{4}-\d{2}-\d{2})/;

const parseStayDate = (value: unknown): string => {
    if (typeof value !== 'string') {
        return '';
    }

    const matched = value.trim().match(ISO_DATE);

    return matched ? matched[1] : '';
};

const hasSpecifiedStayDates = (checkin: unknown, checkout: unknown): boolean => {
    const start = parseStayDate(checkin);
    const end = parseStayDate(checkout);

    return Boolean(start && end && start < end);
};

const addDays = (dateStr: string, days: number): string => {
    const parsed = parseStayDate(dateStr);
    if (!parsed) {
        return '';
    }

    const [year, month, day] = parsed.split('-').map(Number);
    const date = new Date(year, month - 1, day + days);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};

export default function Create({ rooms, selectedRoomId, searchParams = {} }: any) {

    const { post, processing, errors } = useForm({});
    const checkinInputRef = useRef<HTMLInputElement>(null);
    const checkoutInputRef = useRef<HTMLInputElement>(null);

    const [searchQuery, setSearchQuery] = useState({
        room_id: selectedRoomId || '',
        checkin: parseStayDate(searchParams.checkin),
        checkout: parseStayDate(searchParams.checkout),
        adults: Number(searchParams.adults ?? 2),
        children: Number(searchParams.children ?? 0),
        room_count: Number(searchParams.room_count ?? 1),
    });

    const [modalPlan, setModalPlan] = useState<any>(null);
    const [calendarTarget, setCalendarTarget] = useState<{
        planId: number;
        roomId: number;
        planName: string;
        roomName: string;
    } | null>(null);

    const applySearch = (query = searchQuery) => {
        router.get(route('reservations.create'), {
            room_id: query.room_id || undefined,
            checkin: query.checkin || undefined,
            checkout: query.checkout || undefined,
            adults: query.adults,
            children: query.children,
            room_count: query.room_count,
        }, {
            preserveScroll: true,
            replace: true,
        });
    };

    const handleCheckinChange = (value: string) => {
        let newCheckout = searchQuery.checkout;

        if (value && !newCheckout) {
            newCheckout = addDays(value, 1);
        } else if (value && newCheckout) {

            if (new Date(newCheckout) <= new Date(value)) {
                newCheckout = addDays(value, 1);
            }
        }

        const next = { ...searchQuery, checkin: value, checkout: newCheckout };
        setSearchQuery(next);
        if (hasSpecifiedStayDates(next.checkin, next.checkout)) {
            applySearch(next);
        }
    };

    const handleCheckoutChange = (value: string) => {
        let newCheckin = searchQuery.checkin;

        if (value && !newCheckin) {
            newCheckin = addDays(value, -1);
        } else if (value && newCheckin) {

            if (new Date(newCheckin) >= new Date(value)) {
                newCheckin = addDays(value, -1);
            }
        }

        const next = { ...searchQuery, checkin: newCheckin, checkout: value };
        setSearchQuery(next);
        if (hasSpecifiedStayDates(next.checkin, next.checkout)) {
            applySearch(next);
        }
    };

    const submitReservation = (
        planId: number,
        roomId: number,
        checkin: string,
        checkout: string,
    ) => {
        post(route('reservations.details', {
            plan_id: planId,
            room_id: roomId,
            checkin_date: checkin,
            checkout_date: checkout,
            adult_count: searchQuery.adults,
            child_count: searchQuery.children,
            room_count: searchQuery.room_count,
        }));
    };

    const resolveStayDates = () => {
        const checkin = parseStayDate(checkinInputRef.current?.value) || parseStayDate(searchQuery.checkin);
        const checkout = parseStayDate(checkoutInputRef.current?.value) || parseStayDate(searchQuery.checkout);

        return { checkin, checkout };
    };

    const handleReserve = (
        event: React.MouseEvent<HTMLButtonElement>,
        planId: number,
        roomId: number,
        planName: string,
        roomName: string,
    ) => {
        event.preventDefault();
        event.stopPropagation();

        const { checkin, checkout } = resolveStayDates();
        if (!hasSpecifiedStayDates(checkin, checkout)) {
            setCalendarTarget({ planId, roomId, planName, roomName });
            return;
        }

        submitReservation(planId, roomId, checkin, checkout);
    };

    const handleCalendarConfirm = (checkin: string, checkout: string) => {
        if (!calendarTarget) {
            return;
        }

        setSearchQuery({ ...searchQuery, checkin, checkout });
        const { planId, roomId } = calendarTarget;
        setCalendarTarget(null);
        submitReservation(planId, roomId, checkin, checkout);
    };

    const groupedPlans = useMemo(() => {
        const groups: any = {};

        const nights = (searchQuery.checkin && searchQuery.checkout)
            ? Math.max(1, Math.floor((new Date(searchQuery.checkout).getTime() - new Date(searchQuery.checkin).getTime()) / (1000 * 60 * 60 * 24)))
            : 1;

        rooms.forEach((room: any) => {

            if (!room.plans) return;

            room.plans.forEach((plan: any) => {

                const groupKey = plan.name;

                if (!groups[groupKey]) {
                    groups[groupKey] = {
                        ...plan,
                        plan_thumbnail: plan.images?.[0] || '/images/no-image.png',
                        room_options: []
                    };
                }

                const datesSpecified = hasSpecifiedStayDates(searchQuery.checkin, searchQuery.checkout);
                const hasStock = room.current_inventory === null || room.current_inventory >= searchQuery.room_count;

                if (datesSpecified && !hasStock) {
                    return;
                }

                groups[groupKey].room_options.push({
                    room_id: room.id,
                    room_name: room.name,
                    room_image: room.images?.[0] || '/images/no-image.webp',
                    price: ((Number(room.price_per_person) || 0) + (Number(plan.price_per_person) || 0)) * nights,
                    is_available: hasStock,
                    remains: room.current_inventory
                });
            });
        });

        let result = Object.values(groups).filter((g: any) => g.room_options.length > 0);

        if (searchQuery.room_id) {
            const selectedId = parseInt(searchQuery.room_id as string);
            result = result
                .map((g: any) => ({
                    ...g,
                    room_options: g.room_options.filter((opt: any) => opt.room_id === selectedId),
                }))
                .filter((g: any) => g.room_options.length > 0);
        }

        return result;
    }, [rooms, searchQuery.room_id, searchQuery.room_count, searchQuery.checkin, searchQuery.checkout]);

    return (
        <GuestLayout>
            <Head title="宿泊プラン一覧" />

            <div className="pt-[6.5rem]">
                <div className="sticky top-[6.5rem] z-40 border-t border-stone-700 bg-stone-800 text-white">
                    <div className="mx-auto max-w-7xl px-4 py-6">
                    <div className="flex flex-wrap items-center gap-y-6 gap-x-8 text-xs">

                        <div className="flex items-center gap-3">
                            <span className="opacity-70 font-bold">部屋タイプ</span>
                            <select
                                className="bg-stone-700 border-stone-600 text-white h-10 rounded px-4 w-48 text-sm focus:ring-amber-500"
                                value={searchQuery.room_id}
                                onChange={e => setSearchQuery({...searchQuery, room_id: e.target.value})}
                            >
                                <option value="">すべての部屋</option>
                                {rooms.map((r: any) => <option key={r.id} value={r.id}>{r.name}</option>)}
                            </select>
                        </div>

                        <div className="flex items-center gap-3 border-l border-stone-600 pl-8">
                            <span className="opacity-70 font-bold">ご宿泊日程</span>
                            <div className="flex items-center gap-2">
                                <input
                                    ref={checkinInputRef}
                                    type="date"
                                    className="h-10 rounded border-stone-600 bg-stone-700 px-3 text-sm text-white [color-scheme:dark]"
                                    value={searchQuery.checkin}
                                    onChange={e => handleCheckinChange(e.target.value)}
                                />
                                <span className="mx-1">〜</span>
                                <input
                                    ref={checkoutInputRef}
                                    type="date"
                                    className="h-10 rounded border-stone-600 bg-stone-700 px-3 text-sm text-white [color-scheme:dark]"
                                    value={searchQuery.checkout}
                                    onChange={e => handleCheckoutChange(e.target.value)}
                                />
                                {!hasSpecifiedStayDates(searchQuery.checkin, searchQuery.checkout) && (
                                    <span className="rounded bg-stone-600 px-2 py-1 text-[10px] tracking-widest text-stone-200">未定</span>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center gap-6 border-l border-stone-600 pl-8">
                            <div className="flex items-center gap-2">
                                <span className="opacity-70 font-bold">大人</span>
                                <input type="number" min="1" className="bg-stone-700 border-stone-600 w-16 h-10 text-white rounded text-center text-sm" value={searchQuery.adults} onChange={e => setSearchQuery({...searchQuery, adults: parseInt(e.target.value)})} />
                                <span>名</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="opacity-70 font-bold">子供</span>
                                <input type="number" min="0" className="bg-stone-700 border-stone-600 w-16 h-10 text-white rounded text-center text-sm" value={searchQuery.children} onChange={e => setSearchQuery({...searchQuery, children: parseInt(e.target.value)})} />
                                <span>名</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="opacity-70 font-bold">部屋数</span>
                                <input type="number" min="1" className="bg-stone-700 border-stone-600 w-16 h-10 text-white rounded text-center text-sm" value={searchQuery.room_count} onChange={e => setSearchQuery({...searchQuery, room_count: parseInt(e.target.value)})} />
                                <span>室</span>
                            </div>
                        </div>

                        <div className="flex gap-3 ml-auto">
                            <button type="button" className="bg-stone-600 px-6 h-10 rounded hover:bg-stone-500 transition-colors font-medium" onClick={() => {
                                const cleared = {room_id: '', checkin: '', checkout: '', adults: 2, children: 0, room_count: 1};
                                setSearchQuery(cleared);
                                applySearch(cleared);
                            }}>クリア</button>
                            <button type="button" className="bg-amber-700 px-10 h-10 rounded hover:bg-amber-600 transition-colors font-bold tracking-widest shadow-lg" onClick={() => applySearch()}>再検索</button>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

            <section className="py-12 max-w-7xl mx-auto px-4 mt-4">
                <div className="bg-amber-50 border border-amber-200 p-4 mt-10 mb-10 text-xs text-amber-900 leading-loose flex items-start gap-3">
                    <span className="text-lg">info</span>
                    <div>
                        <p>※ 入湯税として大人お一人様150円を別途頂戴いたします。</p>
                        <p>※ キャンセル料は宿泊日の3日前より発生いたしますのでご注意ください。</p>
                    </div>
                </div>

                <div className="space-y-12">
                    {groupedPlans.map((group: any) => (
                        <div key={group.id} className="bg-white border border-stone-200 flex flex-col lg:flex-row shadow-sm overflow-hidden group">

                            <div className="lg:w-1/4 h-64 lg:h-auto overflow-hidden">
                                <img
                                    src={group.plan_thumbnail}
                                    className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    alt={group.name}
                                />
                            </div>

                            <div className="lg:w-2/5 p-8 border-r border-stone-100 flex flex-col justify-between">
                                <div>
                                    <h3 className="text-xl font-bold text-stone-800 mb-4 tracking-wider">{group.name}</h3>
                                    <p className="text-sm text-stone-600 leading-relaxed line-clamp-4">{group.description}</p>
                                </div>
                                <button onClick={() => setModalPlan(group)} className="self-start mt-6 text-[10px] tracking-[0.2em] border border-stone-300 px-6 py-2 hover:bg-stone-800 hover:text-white transition-all">
                                    プラン内容を詳しく見る
                                </button>
                            </div>

                            <div className="flex flex-col gap-1">
                                {group.room_options.map((option: any) => (
                                    <div key={option.room_id} className="flex items-center justify-between bg-stone-50 p-4 rounded border border-stone-100">

                                        <div className="flex-1 min-w-0 pr-4">
                                            <p className="text-sm font-bold text-stone-800 truncate">
                                                {option.room_name}
                                            </p>
                                        </div>

                                        <div className="w-32 text-right pr-6">
                                            <span className="text-xs text-stone-500">1名 </span>
                                            <span className="text-lg font-bold text-amber-900">
                                                ¥{option.price.toLocaleString()}
                                            </span>
                                        </div>

                                        <button
                                            type="button"
                                            onClick={(event) => handleReserve(event, group.id, option.room_id, group.name, option.room_name)}
                                            className="w-40 py-2 bg-stone-800 text-white text-xs tracking-[0.2em] font-bold hover:bg-stone-700 transition-all flex-shrink-0"
                                        >
                                            予約する
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <StayCalendarModal
                show={calendarTarget !== null}
                planId={calendarTarget?.planId ?? 0}
                roomId={calendarTarget?.roomId ?? 0}
                planName={calendarTarget?.planName ?? ''}
                roomName={calendarTarget?.roomName ?? ''}
                roomCount={searchQuery.room_count}
                adultCount={searchQuery.adults}
                childCount={searchQuery.children}
                onClose={() => setCalendarTarget(null)}
                onConfirm={handleCalendarConfirm}
            />

            {modalPlan && (
                <div
                    className="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-stone-900/80 backdrop-blur-sm"
                    onClick={() => setModalPlan(null)}
                >
                    <div
                        className="bg-white w-full max-w-4xl max-h-[85vh] rounded-sm shadow-2xl relative flex flex-row overflow-hidden"
                        onClick={e => e.stopPropagation()}
                    >
                        <button
                            type="button"
                            onClick={() => setModalPlan(null)}
                            className="absolute top-3 right-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-2xl leading-none text-stone-400 hover:text-stone-800"
                            aria-label="閉じる"
                        >
                            &times;
                        </button>

                        <div className="w-[42%] shrink-0 self-stretch overflow-hidden bg-stone-100">
                            <img
                                src={modalPlan.plan_thumbnail || modalPlan.room_options?.[0]?.room_image || '/images/no-image.png'}
                                className="h-full w-full min-h-[220px] object-cover"
                                alt={modalPlan.name}
                            />
                        </div>

                        <div className="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto p-6 sm:p-8">
                            <h2 className="mb-4 shrink-0 border-b border-stone-200 pb-3 pr-8 text-xl font-light tracking-widest sm:text-2xl">
                                {modalPlan.name}
                            </h2>
                            <p className="flex-1 whitespace-pre-wrap text-sm leading-relaxed text-stone-600">
                                {modalPlan.description}
                            </p>
                            <div className="mt-6 shrink-0 border-t border-stone-100 pt-4 text-[10px] text-stone-400">
                                ※ 当プランは大人1名様より承ります。季節によりお料理の内容が異なります。
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </GuestLayout>
    );
}
