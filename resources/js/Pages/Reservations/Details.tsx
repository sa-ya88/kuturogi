import { Head, useForm, usePage } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import ReservationGuestFields, { reservationFieldId } from '@/Components/ReservationGuestFields';
import { useState, useMemo, useEffect } from 'react';
import { PageProps } from '@/types';

import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, Autoplay, EffectFade } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/effect-fade';

const splitPersonName = (value?: string | null): [string, string] => {
    const trimmed = (value ?? '').trim();
    if (!trimmed) {
        return ['', ''];
    }
    const parts = trimmed.split(/[\s　]+/, 2);

    return parts.length === 1 ? [parts[0], ''] : [parts[0], parts[1]];
};

export default function Details({ auth, input, room, plan, optionFees = [], cancelPolicy = [] }: any) {
    const sliderImagesPlan = plan.images?.length ? plan.images : ['/images/room1.webp'];
    const shouldUseSwiperPlan = sliderImagesPlan.length > 1;
    const sliderImagesRoom = room.images?.length ? room.images : ['/images/room1.webp'];
    const shouldUseSwiperRoom = sliderImagesRoom.length > 1;
    const choiceOptions = plan?.choice_options ?? [];
    const isAuthenticated = Boolean(auth?.user);
    const allowRegistration = usePage<PageProps>().props.demo?.allowRegistration !== false;
    const member = auth?.user;
    const [memberLast, memberFirst] = splitPersonName(member?.name);
    const [memberLastKana, memberFirstKana] = splitPersonName(member?.name_kana);

    const { data, setData, post, processing, errors, transform } = useForm('reservation-details', {
        ...input,
        check_in_date: input?.check_in_date || input?.checkin_date || '',
        check_out_date: input?.check_out_date || input?.checkout_date || '',
        adult_count: Number(input?.adult_count ?? 2) || 2,
        child_count: Number(input?.child_count ?? 0) || 0,
        room_count: Number(input?.room_count ?? 1) || 1,
        last_name: input?.last_name || memberLast,
        first_name: input?.first_name || memberFirst,
        last_name_kana: input?.last_name_kana || memberLastKana,
        first_name_kana: input?.first_name_kana || memberFirstKana,
        tel: input?.tel ?? '',
        email: input?.email || member?.email || '',
        zip_code: input?.zip_code || member?.zip_code || '',
        address: input?.address || member?.address || '',
        building: input?.building ?? '',
        email_magazine: input?.email_magazine === true || input?.email_magazine === 1 || input?.email_magazine === '1',
        register_membership:
            allowRegistration
            && (input?.register_membership === true || input?.register_membership === 1 || input?.register_membership === 'on'),
        payment_method: input?.payment_method === 'credit' ? 'credit' : 'local',
        selected_choices: choiceOptions.map((_: any, index: number) => {
            const saved = input?.selected_choices;
            if (Array.isArray(saved)) {
                return saved[index] ?? '';
            }
            if (saved && typeof saved === 'object') {
                return saved[index] ?? saved[String(index)] ?? '';
            }

            return '';
        }),
        selected_option_ids: Array.isArray(input?.selected_option_ids)
            ? input.selected_option_ids.map((id: any) => Number(id))
            : [],
        representatives: Array.from(
            { length: Math.max(1, Number(input?.room_count) || 1) },
            (_, index) => input?.representatives?.[index] ?? (index === 0 ? (member?.name ?? '') : ''),
        ),
    });

    useEffect(() => {
        const count = Math.max(1, Number(data.room_count) || 1);
        const next = Array.from({ length: count }, (_, index) => data.representatives?.[index] ?? '');
        const same =
            data.representatives?.length === next.length &&
            data.representatives.every((value: string, index: number) => value === next[index]);
        if (!same) {
            setData('representatives', next);
        }
    }, [data.room_count]);

    // バリデーションエラー用のローカルstate
    const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});
    const [quote, setQuote] = useState<any>(null);

    // 宿泊日数を計算
    const nights = useMemo(() => {
        if (data.check_in_date && data.check_out_date) {
            return Math.max(1, Math.floor((new Date(data.check_out_date).getTime() - new Date(data.check_in_date).getTime()) / (1000 * 60 * 60 * 24)));
        }
        return 1;
    }, [data.check_in_date, data.check_out_date]);

    const pricePerPersonPerNight = quote?.base_per_person_per_night
        ?? (plan.price_per_person + room.price_per_person);
    const totalPrice = quote?.total ?? 0;
    const childPercent = quote?.child_percent ?? 70;

    useEffect(() => {
        if (!data.check_in_date || !data.check_out_date || !room?.id || !plan?.id) {
            return;
        }
        if (new Date(data.check_in_date) >= new Date(data.check_out_date)) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                const response = await fetch(route('reservations.quote'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        plan_id: plan.id,
                        room_id: room.id,
                        checkin_date: data.check_in_date,
                        checkout_date: data.check_out_date,
                        adult_count: Number(data.adult_count) || 1,
                        child_count: Number(data.child_count) || 0,
                        room_count: Number(data.room_count) || 1,
                        selected_option_ids: data.selected_option_ids ?? [],
                    }),
                    signal: controller.signal,
                });
                if (!response.ok) {
                    return;
                }
                setQuote(await response.json());
            } catch (error: any) {
                if (error?.name !== 'AbortError') {
                    console.error(error);
                }
            }
        }, 250);

        return () => {
            controller.abort();
            window.clearTimeout(timer);
        };
    }, [
        data.check_in_date,
        data.check_out_date,
        data.adult_count,
        data.child_count,
        data.room_count,
        data.selected_option_ids,
        plan?.id,
        room?.id,
    ]);

    const toggleOption = (optionId: number) => {
        const current = Array.isArray(data.selected_option_ids) ? data.selected_option_ids : [];
        if (current.includes(optionId)) {
            setData('selected_option_ids', current.filter((id: number) => id !== optionId));
        } else {
            setData('selected_option_ids', [...current, optionId]);
        }
    };

    const scrollToField = (fieldKey: string) => {
        window.requestAnimationFrame(() => {
            const el = document.getElementById(reservationFieldId(fieldKey));
            if (!el) {
                return;
            }

            const header = document.querySelector('nav.fixed');
            const headerHeight = header instanceof HTMLElement ? header.getBoundingClientRect().height : 104;
            const top = el.getBoundingClientRect().top + window.scrollY - headerHeight - 16;
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
            if (el instanceof HTMLElement) {
                el.focus({ preventScroll: true });
            }
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        
        // バリデーション
        const newErrors: Record<string, string> = {};
        
        // 宿泊日程のバリデーション
        if (!data.check_in_date) {
            newErrors.check_in_date = 'チェックイン日を入力してください';
        }
        if (!data.check_out_date) {
            newErrors.check_out_date = 'チェックアウト日を入力してください';
        }
        
        // チェックイン日がチェックアウト日より前であることを確認
        if (data.check_in_date && data.check_out_date) {
            if (new Date(data.check_in_date) >= new Date(data.check_out_date)) {
                newErrors.check_out_date = 'チェックアウト日はチェックイン日より後の日付を選択してください';
            }
        }

        // お客様情報のバリデーション（会員は登録情報を使うため入力チェックしない）
        if (!isAuthenticated) {
            if (!data.last_name?.trim()) {
                newErrors.last_name = 'お名前（姓）を入力してください';
            }
            if (!data.first_name?.trim()) {
                newErrors.first_name = 'お名前（名）を入力してください';
            }
            if (!data.last_name_kana?.trim()) {
                newErrors.last_name_kana = 'お名前（姓・ひらがな）を入力してください';
            }
            if (!data.first_name_kana?.trim()) {
                newErrors.first_name_kana = 'お名前（名・ひらがな）を入力してください';
            }
            if (!data.tel?.trim()) {
                newErrors.tel = '電話番号を入力してください';
            }
            if (!data.email?.trim()) {
                newErrors.email = 'メールアドレスを入力してください';
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
                newErrors.email = '有効なメールアドレスを入力してください';
            }
            if (!data.zip_code?.trim()) {
                newErrors.zip_code = '郵便番号を入力してください';
            }
            if (!data.address?.trim()) {
                newErrors.address = '住所を入力してください';
            }
        }

        const roomCount = Math.max(1, Number(data.room_count) || 1);
        const primaryName = isAuthenticated
            ? (member?.name ?? `${data.last_name?.trim() ?? ''} ${data.first_name?.trim() ?? ''}`).trim()
            : `${data.last_name?.trim() ?? ''} ${data.first_name?.trim() ?? ''}`.trim();
        const representatives = Array.from({ length: roomCount }, (_, index) => {
            if (index === 0) {
                return (data.representatives?.[index] || primaryName).trim();
            }
            return (data.representatives?.[index] ?? '').trim();
        });

        representatives.forEach((name, index) => {
            if (!name) {
                newErrors[`representatives_${index}`] = `${index + 1}室目の代表者名を入力してください`;
            }
        });

        choiceOptions.forEach((option: any, index: number) => {
            if (!data.selected_choices?.[index]?.trim()) {
                newErrors[`selected_choices_${index}`] = `「${option.prompt}」を選択してください`;
            }
        });
        
        if (Object.keys(newErrors).length > 0) {
            setValidationErrors(newErrors);
            scrollToField(Object.keys(newErrors)[0]);
            return;
        }
        
        setValidationErrors({});
        transform((formData) => ({
            ...formData,
            representatives,
        }));
        post(route('reservations.confirm'));
    };

    return (
        <GuestLayout>
            <Head title="予約詳細入力" />
            <section className="pt-32 pb-20 max-w-6xl mx-auto px-4">
                <h1 className="text-2xl font-light tracking-widest text-center mb-12">予約内容詳細の入力</h1>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <div className="lg:col-span-2 space-y-10">
                        
                        {/* 1. プラン情報 */}
                        <div className="bg-white border overflow-hidden">
                            {shouldUseSwiperPlan ? (
                                <Swiper
                                    modules={[Navigation, Pagination, Autoplay, EffectFade]}
                                    effect="fade"
                                    fadeEffect={{
                                        crossFade: true
                                    }}
                                    speed={1000}
                                    autoplay={{
                                        delay: 3000,
                                        disableOnInteraction: false,
                                    }}
                                    navigation
                                    pagination={{ clickable: true }}
                                    loop={true}
                                    className="h-64"
                                >
                                    {sliderImagesPlan.map((image: string, index: number) => (
                                        <SwiperSlide key={index}>
                                            <img src={image} className="w-full h-full object-cover" alt="プラン画像" />
                                        </SwiperSlide>
                                    ))}
                                </Swiper>
                            ) : (
                                <img src={sliderImagesPlan[0]} className="w-full h-64 object-cover" alt="プラン画像" />
                            )}
                            <div className="p-8">
                                <h2 className="text-xl font-bold mb-4">{plan.name}</h2>
                                <p className="whitespace-pre-wrap text-sm leading-loose text-stone-600">{plan.description}</p>
                            </div>
                        </div>

                        {/* 2. 部屋情報と設備 */}
                        <div className="bg-white border p-8">
                            <h2 className="text-lg font-bold border-b pb-2 mb-6">お部屋：{room.name}</h2>
                            <div className="grid grid-cols-2 gap-8 mb-8">
                                {shouldUseSwiperRoom ? (
                                    <div className="w-full min-w-0 max-w-full overflow-hidden relative">
                                        <Swiper
                                            modules={[Navigation, Pagination, Autoplay, EffectFade]}
                                            effect="fade"
                                            fadeEffect={{
                                                crossFade: true
                                            }}
                                            speed={1000}
                                            autoplay={{
                                                delay: 3000,
                                                disableOnInteraction: false,
                                            }}
                                            navigation
                                            pagination={{ clickable: true }}
                                            loop={true}
                                            className="h-40"
                                        >
                                            {sliderImagesRoom.map((image: string, index: number) => (
                                                <SwiperSlide key={index}>
                                                    <img src={image} className="w-full h-full object-cover" alt="部屋画像" />
                                                </SwiperSlide>
                                            ))}
                                        </Swiper>
                                    </div>
                                ) : (
                                    <img src={sliderImagesRoom[0]} className="w-full h-40 object-cover rounded" alt="部屋画像" />
                                )}
                                <div className="text-sm space-y-2">
                                    <p className="font-bold text-stone-400 text-[10px] uppercase">Room Facilities</p>
                                    <ul className="list-disc list-inside text-stone-600">
                                        <li>無料Wi-Fi完備</li>
                                        <li>加湿空気清浄機</li>
                                        <li>洗浄機能付きトイレ</li>
                                        <li>専用アメニティ一式</li>
                                    </ul>
                                </div>
                            </div>

                            <h2 className="font-bold border-b pb-2 mb-4 mt-10">留意事項</h2>
                            <ul className="text-xs text-stone-500 space-y-2 list-decimal list-inside leading-loose">
                                <li>当館は全館禁煙となっております（喫煙所は1Fにございます）。</li>
                                <li>ご夕食の最終開始時間は19:30となります。</li>
                                <li>アレルギーをお持ちの方は事前にお知らせください。</li>
                                <li>送迎バスをご利用の場合は前日までにお電話にてご予約ください。</li>
                                <li>入湯税（150円）は現地にて別途頂戴いたします。</li>
                            </ul>

                            <h2 className="font-bold border-b pb-2 mb-4 mt-10">キャンセルポリシー</h2>
                            <p className="text-xs text-red-600 leading-loose">
                                {(cancelPolicy?.length ? cancelPolicy : ['キャンセルポリシーは準備中です']).map((line: string) => (
                                    <span key={line}>
                                        {line}
                                        <br />
                                    </span>
                                ))}
                            </p>
                        </div>

                        {/* 2.5 チェックイン・チェックアウト日 */}
                        <div className="bg-white border p-8">
                            <h2 className="text-lg font-bold border-b pb-4 mb-6">宿泊日程</h2>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-bold mb-2">チェックイン日 <span className="text-red-600">*</span></label>
                                    <input 
                                        id={reservationFieldId('check_in_date')}
                                        type="date" 
                                        value={data.check_in_date} 
                                        onChange={e => setData('check_in_date', e.target.value)} 
                                        className={`w-full border-stone-300 ${validationErrors.check_in_date ? 'border-red-500' : ''}`}
                                    />
                                    {validationErrors.check_in_date && (
                                        <p className="text-red-600 text-sm mt-2">{validationErrors.check_in_date}</p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-2">チェックアウト日 <span className="text-red-600">*</span></label>
                                    <input 
                                        id={reservationFieldId('check_out_date')}
                                        type="date" 
                                        value={data.check_out_date} 
                                        onChange={e => setData('check_out_date', e.target.value)} 
                                        className={`w-full border-stone-300 ${validationErrors.check_out_date ? 'border-red-500' : ''}`}
                                    />
                                    {validationErrors.check_out_date && (
                                        <p className="text-red-600 text-sm mt-2">{validationErrors.check_out_date}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* 4. 人数・室数変更フォーム */}
                        <div className="bg-stone-100 p-8 border">
                            <h2 className="font-bold mb-6 tracking-widest text-center">宿泊人数の変更</h2>
                            <div className="flex justify-center gap-12">
                                <div className="flex items-center gap-3">
                                    <span className="text-sm">大人</span>
                                    <input type="number" value={data.adult_count} onChange={e => setData('adult_count', parseInt(e.target.value))} className="w-20 border-stone-300" />
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className="text-sm">子供</span>
                                    <input type="number" value={data.child_count} onChange={e => setData('child_count', parseInt(e.target.value))} className="w-20 border-stone-300" />
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className="text-sm">部屋数</span>
                                    <input type="number" min={1} value={data.room_count} onChange={e => setData('room_count', Math.max(1, parseInt(e.target.value) || 1))} className="w-20 border-stone-300" />
                                </div>
                            </div>
                            <p className="text-xs text-stone-500 text-center mt-4">
                                複数室のご予約は同一プラン・各室同じ人数の場合のみ可能です。各部屋に代表者名が必要です。
                            </p>
                            <p className="text-xs text-stone-500 text-center mt-2">
                                子供は小学生以下が対象です。中学生以上は大人としてご予約ください。
                            </p>
                        </div>

                        <ReservationGuestFields
                            data={data}
                            setData={(key, value) => setData(key as any, value as any)}
                            validationErrors={validationErrors}
                            isAuthenticated={isAuthenticated}
                            member={member}
                        />

                        {choiceOptions.length > 0 && (
                            <div className="bg-white border p-8 space-y-6">
                                <h2 className="text-lg font-bold border-b pb-4">プランの選択項目</h2>
                                {choiceOptions.map((option: any, index: number) => (
                                    <div key={index}>
                                        <label className="block text-sm font-bold mb-2">
                                            {option.prompt} <span className="text-red-600">*</span>
                                        </label>
                                        <select
                                            id={reservationFieldId(`selected_choices_${index}`)}
                                            value={data.selected_choices[index] ?? ''}
                                            onChange={(e) => {
                                                const next = [...(data.selected_choices ?? [])];
                                                next[index] = e.target.value;
                                                setData('selected_choices', next);
                                            }}
                                            className={`w-full border-stone-300 ${validationErrors[`selected_choices_${index}`] ? 'border-red-500' : ''}`}
                                        >
                                            <option value="">選択してください</option>
                                            {(option.choices ?? []).map((choice: any, choiceIndex: number) => (
                                                <option key={choiceIndex} value={choice.label}>
                                                    {choice.label}
                                                </option>
                                            ))}
                                        </select>
                                        {validationErrors[`selected_choices_${index}`] && (
                                            <p className="text-red-600 text-sm mt-2">{validationErrors[`selected_choices_${index}`]}</p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}

                        {optionFees?.length > 0 && (
                            <div className="bg-white border p-8">
                                <h2 className="text-lg font-bold border-b pb-4 mb-6">オプション</h2>
                                <div className="space-y-3">
                                    {optionFees.map((option: any) => (
                                        <label key={option.id} className="flex items-start gap-3 p-4 border cursor-pointer hover:bg-stone-50">
                                            <input
                                                type="checkbox"
                                                checked={(data.selected_option_ids ?? []).includes(option.id)}
                                                onChange={() => toggleOption(option.id)}
                                                className="mt-1 text-stone-800"
                                            />
                                            <div className="flex-1">
                                                <div className="flex justify-between gap-4">
                                                    <span className="font-bold">{option.name}</span>
                                                    <span>¥{Number(option.price).toLocaleString()}</span>
                                                </div>
                                                {option.description && (
                                                    <p className="text-xs text-stone-500 mt-1">{option.description}</p>
                                                )}
                                            </div>
                                        </label>
                                    ))}
                                </div>
                                <p className="text-xs text-stone-500 mt-4">予約1件あたりの定額です（泊数・室数に関係なく1回）</p>
                            </div>
                        )}

                        {/* 6. お支払い方法 */}
                        <div className="bg-white border p-8">
                            <h2 className="text-lg font-bold border-b pb-4 mb-6">お支払い方法の選択</h2>
                            <div className="space-y-4">
                                <label className="flex items-center gap-4 p-4 border cursor-pointer hover:bg-stone-50">
                                    <input type="radio" name="payment" value="local" checked={data.payment_method === 'local'} onChange={e => setData('payment_method', e.target.value)} className="text-stone-800" />
                                    <div>
                                        <span className="font-bold">現地決済</span>
                                        <p className="text-xs text-stone-500">チェックアウト時にフロントにてお支払いください。</p>
                                    </div>
                                </label>
                                <label className="flex items-center gap-4 p-4 border cursor-pointer hover:bg-stone-50">
                                    <input type="radio" name="payment" value="credit" checked={data.payment_method === 'credit'} onChange={e => setData('payment_method', e.target.value)} className="text-stone-800" />
                                    <div>
                                        <span className="font-bold">クレジットカード（オンライン決済）</span>
                                        <p className="text-xs text-stone-500">次の確認画面でカード情報を入力してください。チェックイン時に引き落としが確定します。</p>
                                    </div>
                                </label>
                            </div>

                            {data.payment_method === 'credit' && (
                                <div className="mt-6 p-6 bg-stone-50 border-l-2 border-stone-300">
                                    <h3 className="text-xs font-bold text-stone-800 mb-3 tracking-widest uppercase">お支払いに関する注意事項</h3>
                                    <ul className="text-[11px] text-stone-600 space-y-2 list-disc list-inside leading-relaxed">
                                        <li>ご利用いただけるカードは、VISA、Mastercard、JCB、AMEX、Dinersとなります。</li>
                                        <li>キャンセル規定に基づきキャンセル料が発生する場合、登録済みのカードより引き落としさせていただくことがあります。</li>
                                    </ul>
                                </div>
                            )}
                        </div>
                        

                    </div>

                    {/* 右サイドバー：料金内訳 */}
                    <div className="h-fit sticky top-40">
                        <div className="bg-stone-800 text-white p-6 shadow-xl">
                            <h3 className="text-lg font-bold mb-6 border-b border-stone-600 pb-2 tracking-widest">料金内訳</h3>
                            <div className="space-y-4 text-sm mb-8">
                                <div className="flex justify-between">
                                    <span>大人 (¥{Number(pricePerPersonPerNight).toLocaleString()} × {data.adult_count}名 × {nights}泊)</span>
                                    <span>¥{Number(quote?.nights?.reduce((sum: number, n: any) => sum + (n.adult_amount || 0), 0) ?? (pricePerPersonPerNight * data.adult_count * nights * data.room_count)).toLocaleString()}</span>
                                </div>
                                {data.child_count > 0 && (
                                    <div className="flex justify-between">
                                        <span>子供 {childPercent}% (¥{Math.round(pricePerPersonPerNight * childPercent / 100).toLocaleString()} × {data.child_count}名 × {nights}泊)</span>
                                        <span>¥{Number(quote?.nights?.reduce((sum: number, n: any) => sum + (n.child_amount || 0), 0) ?? 0).toLocaleString()}</span>
                                    </div>
                                )}
                                <div className="flex justify-between opacity-60 text-xs">
                                    <span>部屋数</span>
                                    <span>× {data.room_count}室</span>
                                </div>
                                {(quote?.summary_adjustments ?? []).length > 0 && (
                                    <div className="text-xs text-amber-200 space-y-1">
                                        {(quote.summary_adjustments as string[]).map((label) => (
                                            <p key={label}>※ {label}</p>
                                        ))}
                                    </div>
                                )}
                                {(quote?.selected_options ?? []).map((option: any) => (
                                    <div key={option.id} className="flex justify-between text-xs">
                                        <span>オプション: {option.name}</span>
                                        <span>¥{Number(option.price).toLocaleString()}</span>
                                    </div>
                                ))}
                                <div className="border-t border-stone-600 pt-4 flex justify-between items-end">
                                    <span className="text-xs">合計金額 (税込)</span>
                                    <span className="text-2xl font-serif text-amber-400">¥{Number(totalPrice).toLocaleString()}</span>
                                </div>
                            </div>
                            <button 
                                onClick={submit}
                                disabled={processing}
                                className="w-full bg-amber-700 text-white py-4 font-bold tracking-[0.2em] hover:bg-amber-600 transition shadow-lg"
                            >
                                最終確認へ進む
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}
