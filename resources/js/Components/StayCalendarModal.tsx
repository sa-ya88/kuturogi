import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

type CalendarDay = {
    date: string;
    in_month: boolean;
    available: boolean;
    remains: number;
    price: number | null;
};

type CalendarPayload = {
    year: number;
    month: number;
    low_stock_threshold: number;
    days: CalendarDay[];
};

type StayCalendarModalProps = {
    show: boolean;
    planId: number;
    roomId: number;
    planName: string;
    roomName: string;
    roomCount: number;
    adultCount: number;
    childCount: number;
    onClose: () => void;
    onConfirm: (checkin: string, checkout: string) => void;
};

const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

const pad2 = (value: number): string => String(value).padStart(2, '0');

const toDateString = (year: number, month: number, day: number): string =>
    `${year}-${pad2(month)}-${pad2(day)}`;

const addDays = (dateStr: string, days: number): string => {
    const [year, month, day] = dateStr.split('-').map(Number);
    const date = new Date(year, month - 1, day + days);

    return toDateString(date.getFullYear(), date.getMonth() + 1, date.getDate());
};

const formatJaDate = (dateStr: string): string => {
    const [year, month, day] = dateStr.split('-').map(Number);

    return `${year}年${month}月${day}日`;
};

const nightsBetween = (checkin: string, checkout: string): number => {
    const start = new Date(checkin);
    const end = new Date(checkout);

    return Math.max(0, Math.round((end.getTime() - start.getTime()) / 86400000));
};

const eachNight = (checkin: string, checkout: string): string[] => {
    const nights: string[] = [];
    let cursor = checkin;
    while (cursor < checkout) {
        nights.push(cursor);
        cursor = addDays(cursor, 1);
    }

    return nights;
};

export default function StayCalendarModal({
    show,
    planId,
    roomId,
    planName,
    roomName,
    roomCount,
    adultCount,
    childCount,
    onClose,
    onConfirm,
}: StayCalendarModalProps) {
    const now = new Date();
    const [year, setYear] = useState(now.getFullYear());
    const [month, setMonth] = useState(now.getMonth() + 1);
    const [payload, setPayload] = useState<CalendarPayload | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [checkin, setCheckin] = useState('');
    const [checkout, setCheckout] = useState('');
    const [rangeError, setRangeError] = useState('');
    const dayCache = useRef<Map<string, CalendarDay>>(new Map());

    const dayMap = useMemo(() => {
        const map = new Map<string, CalendarDay>(dayCache.current);
        payload?.days.forEach((day) => {
            map.set(day.date, day);
            dayCache.current.set(day.date, day);
        });

        return map;
    }, [payload]);

    const loadMonth = useCallback(async (nextYear: number, nextMonth: number, signal?: AbortSignal) => {
        setLoading(true);
        setError('');
        try {
            const query = new URLSearchParams({
                plan_id: String(planId),
                room_id: String(roomId),
                year: String(nextYear),
                month: String(nextMonth),
                room_count: String(roomCount),
                adult_count: String(adultCount),
                child_count: String(childCount),
            });
            const response = await fetch(`${route('reservations.stay-calendar')}?${query.toString()}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal,
            });
            if (!response.ok) {
                throw new Error('calendar');
            }
            setPayload(await response.json());
        } catch (err: any) {
            if (err?.name !== 'AbortError') {
                setError('カレンダーを読み込めませんでした。時間をおいて再度お試しください。');
            }
        } finally {
            setLoading(false);
        }
    }, [planId, roomId, roomCount, adultCount, childCount]);

    useEffect(() => {
        if (!show) {
            return;
        }
        const today = new Date();
        setYear(today.getFullYear());
        setMonth(today.getMonth() + 1);
        setCheckin('');
        setCheckout('');
        setRangeError('');
        setPayload(null);
        dayCache.current = new Map();
    }, [show, planId, roomId]);

    useEffect(() => {
        if (!show) {
            return;
        }
        const controller = new AbortController();
        void loadMonth(year, month, controller.signal);

        return () => controller.abort();
    }, [show, year, month, loadMonth]);

    const shiftMonth = (delta: number) => {
        const next = new Date(year, month - 1 + delta, 1);
        setYear(next.getFullYear());
        setMonth(next.getMonth() + 1);
    };

    const rangeIsBookable = (start: string, end: string): boolean => {
        if (nightsBetween(start, end) < 1) {
            return false;
        }

        return eachNight(start, end).every((date) => dayMap.get(date)?.available);
    };

    const handleDayClick = (day: CalendarDay) => {
        setRangeError('');

        const startNewRange = () => {
            if (!day.available) {
                return;
            }
            setCheckin(day.date);
            setCheckout(addDays(day.date, 1));
        };

        if (!checkin || day.date <= checkin) {
            startNewRange();
            return;
        }

        if (!rangeIsBookable(checkin, day.date)) {
            setRangeError('選択期間に満室の日が含まれるため、別の日程をお選びください。');
            return;
        }

        setCheckout(day.date);
    };

    const nights = checkin && checkout ? nightsBetween(checkin, checkout) : 0;
    const canConfirm = nights >= 1 && rangeIsBookable(checkin, checkout);

    if (!show) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-[110] flex items-center justify-center bg-stone-900/80 p-4 backdrop-blur-sm"
            onClick={onClose}
        >
            <div
                className="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-sm bg-white shadow-2xl"
                onClick={(event) => event.stopPropagation()}
            >
                <div className="flex items-start justify-between border-b border-stone-100 px-5 py-3">
                    <div className="min-w-0 pr-2">
                        <p className="text-[10px] tracking-widest text-stone-400">ご宿泊日程を選択</p>
                        <h2 className="mt-0.5 truncate text-base font-light tracking-wider text-stone-800">{planName}</h2>
                        <p className="truncate text-xs text-stone-500">{roomName}</p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xl leading-none text-stone-400 hover:text-stone-800"
                        aria-label="閉じる"
                    >
                        &times;
                    </button>
                </div>

                <div className="px-5 py-3.5">
                    <div className="mb-2.5 flex items-center justify-center gap-6 text-stone-700">
                        <button
                            type="button"
                            onClick={() => shiftMonth(-1)}
                            className="flex h-7 w-7 items-center justify-center text-lg text-stone-400 hover:text-stone-800"
                            aria-label="前の月"
                        >
                            ‹
                        </button>
                        <p className="min-w-[7.5rem] text-center text-base tracking-widest">
                            {month}月 {year}
                        </p>
                        <button
                            type="button"
                            onClick={() => shiftMonth(1)}
                            className="flex h-7 w-7 items-center justify-center text-lg text-stone-400 hover:text-stone-800"
                            aria-label="次の月"
                        >
                            ›
                        </button>
                    </div>

                    <div className="grid grid-cols-7 text-center text-[11px] text-stone-400">
                        {WEEKDAYS.map((label) => (
                            <div key={label} className="py-1.5">
                                {label}
                            </div>
                        ))}
                    </div>

                    <div className="grid grid-cols-7 border-t border-stone-100">
                        {(payload?.days ?? []).map((day) => {
                            const dayNumber = Number(day.date.slice(-2));
                            const inStay = Boolean(checkin && checkout && day.date >= checkin && day.date < checkout);
                            const isCheckin = checkin === day.date;
                            const isCheckout = checkout === day.date;
                            const showBadge =
                                day.available &&
                                day.remains > 0 &&
                                day.remains < (payload?.low_stock_threshold ?? 10);
                            const disabledLook = day.in_month && !day.available;

                            return (
                                <button
                                    key={day.date}
                                    type="button"
                                    onClick={() => handleDayClick(day)}
                                    className={[
                                        'relative flex h-[3.25rem] flex-col items-center justify-center border-b border-r border-stone-100 text-center last:border-r-0',
                                        day.available || (Boolean(checkin) && day.date > checkin)
                                            ? 'cursor-pointer'
                                            : 'cursor-default',
                                        inStay || isCheckin || isCheckout ? 'bg-orange-200/90' : 'bg-white',
                                    ].join(' ')}
                                >
                                    {showBadge && (
                                        <span className="absolute right-0.5 top-0.5 flex h-3.5 min-w-3.5 items-center justify-center bg-[#4c2a6e] px-0.5 text-[9px] font-bold leading-none text-white">
                                            {day.remains}
                                        </span>
                                    )}
                                    <span
                                        className={[
                                            'text-xs leading-none',
                                            !day.in_month ? 'text-stone-200' : disabledLook ? 'text-stone-300' : 'text-stone-700',
                                        ].join(' ')}
                                    >
                                        {pad2(dayNumber)}
                                    </span>
                                    {day.available && day.price !== null && (
                                        <span className="mt-0.5 text-[10px] leading-none text-stone-400">
                                            {day.price.toLocaleString()}
                                        </span>
                                    )}
                                    {disabledLook && (
                                        <span
                                            className="pointer-events-none absolute inset-1 bg-[linear-gradient(to_top_right,transparent_calc(50%-0.5px),#d6d3d1_calc(50%-0.5px),#d6d3d1_calc(50%+0.5px),transparent_calc(50%+0.5px))]"
                                            aria-hidden
                                        />
                                    )}
                                </button>
                            );
                        })}
                    </div>

                    {loading && (
                        <p className="mt-2 text-center text-[10px] text-stone-400">読み込み中...</p>
                    )}
                    {error && <p className="mt-2 text-center text-[10px] text-red-600">{error}</p>}
                    {rangeError && <p className="mt-2 text-center text-[10px] text-red-600">{rangeError}</p>}

                    <div className="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-[10px] text-stone-500">
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="inline-flex items-center gap-1">
                                <span className="inline-block h-2.5 w-2.5 bg-[#4c2a6e]" />
                                残り10部屋未満
                            </span>
                            <span className="inline-flex items-center gap-1">
                                <span className="inline-block h-2.5 w-2.5 bg-orange-300" />
                                ご宿泊予定期間
                            </span>
                        </div>
                        <span>通貨: ¥</span>
                    </div>

                    <div className="mt-3 flex flex-col gap-2 border-t border-stone-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-xs text-stone-600">
                            {checkin && checkout
                                ? `${formatJaDate(checkin)} 〜 ${formatJaDate(checkout)}（${nights}泊）`
                                : 'カレンダーからチェックイン日を選択してください。'}
                        </p>
                        <button
                            type="button"
                            disabled={!canConfirm}
                            onClick={() => onConfirm(checkin, checkout)}
                            className="shrink-0 bg-stone-800 px-4 py-1.5 text-[10px] font-bold tracking-[0.2em] text-white transition-all hover:bg-stone-700 disabled:cursor-not-allowed disabled:bg-stone-300"
                        >
                            この日程で予約する
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
