import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';

// 日付を指定日数分進める/戻す ヘルパー関数
const addDays = (dateStr: string, days: number): string => {
    const date = new Date(dateStr);
    date.setDate(date.getDate() + days);
    return date.toISOString().split('T')[0];
};

// 今日の日付を YYYY-MM-DD 形式で取得
const getTodayDate = (): string => {
    return new Date().toISOString().split('T')[0];
};

export default function Create({ rooms, selectedRoomId }: any) {

    const { post, processing, errors } = useForm({});
    
    const today = getTodayDate();
    const tomorrow = addDays(today, 1);
    
    const [searchQuery, setSearchQuery] = useState({
        room_id: selectedRoomId || '',
        checkin: today,
        checkout: tomorrow,
        adults: 2,
        children: 0,
        room_count: 1,
    });

    const [modalPlan, setModalPlan] = useState<any>(null);

    const handleCheckinChange = (value: string) => {
        let newCheckout = searchQuery.checkout;
        
        // checkoutが空の場合、checkinの翌日を自動設定
        if (value && !newCheckout) {
            newCheckout = addDays(value, 1);
        } else if (value && newCheckout) {
            // checkoutがcheckIn以前の場合、checkoutをcheckinの翌日に設定
            if (new Date(newCheckout) <= new Date(value)) {
                newCheckout = addDays(value, 1);
            }
        }
        
        setSearchQuery({ ...searchQuery, checkin: value, checkout: newCheckout });
    };

    const handleCheckoutChange = (value: string) => {
        let newCheckin = searchQuery.checkin;
        
        // checkinが空の場合、checkoutの前日を自動設定
        if (value && !newCheckin) {
            newCheckin = addDays(value, -1);
        } else if (value && newCheckin) {
            // checkinがcheckoutと同日以降の場合、checkinをcheckoutの前日に設定
            if (new Date(newCheckin) >= new Date(value)) {
                newCheckin = addDays(value, -1);
            }
        }
        
        setSearchQuery({ ...searchQuery, checkin: newCheckin, checkout: value });
    };

    const handleReserve = (planId: number, roomId: number) => {
        post(route('reservations.details', {
            plan_id: planId,
            room_id: roomId,
            checkin_date: searchQuery.checkin,
            checkout_date: searchQuery.checkout,
            adult_count: searchQuery.adults,
            child_count: searchQuery.children,
            room_count: searchQuery.room_count,
        }));
    };
    
    const groupedPlans = useMemo(() => {
        const groups: any = {};

        // 宿泊日数を計算（日付が指定されていない場合は1泊と仮定）
        const nights = (searchQuery.checkin && searchQuery.checkout) 
            ? Math.max(1, Math.floor((new Date(searchQuery.checkout).getTime() - new Date(searchQuery.checkin).getTime()) / (1000 * 60 * 60 * 24)))
            : 1;

        // 1. 各部屋にぶら下がっているプランをループ
        rooms.forEach((room: any) => {
            // room.plans が undefined や空配列でないか確認
            if (!room.plans) return;

            room.plans.forEach((plan: any) => {
                // プラン名（またはID）をキーにしてグループ化
                const groupKey = plan.name; 

                if (!groups[groupKey]) {
                    groups[groupKey] = { 
                        ...plan, 
                        plan_thumbnail: plan.images?.[0] || '/images/no-image.png',
                        room_options: [] 
                    };
                }

                // 在庫判定（日付未選択時は null なので通るはず）
                const hasStock = room.current_inventory === null || room.current_inventory >= searchQuery.room_count;

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

        let result = Object.values(groups);

        // 2. フィルタリング（デバッグのため一旦ゆるく設定）
        if (searchQuery.room_id) {
            const selectedId = parseInt(searchQuery.room_id as string);
            result = result.filter((g: any) => 
                g.room_options.some((opt: any) => opt.room_id === selectedId)
            );
        }

        return result;
    }, [rooms, searchQuery.room_id, searchQuery.room_count, searchQuery.checkin, searchQuery.checkout]);


    return (
        <GuestLayout>
            <Head title="宿泊プラン一覧" />

            {/* 1. 検索バー */}
            <div className="sticky top-20 z-50 bg-stone-800 text-white border-t border-stone-700">
                <div className="max-w-7xl mx-auto px-4 py-6">
                    <div className="flex flex-wrap items-center gap-y-6 gap-x-8 text-xs">
                        {/* 部屋タイプ */}
                        <div className="flex items-center gap-3">
                            <span className="opacity-70 font-bold">部屋タイプ</span>
                            <select 
                                className="bg-stone-700 border-stone-600 text-white h-10 rounded px-4 w-48 text-sm focus:ring-amber-500"
                                value={searchQuery.room_id} 
                                onChange={e => setSearchQuery({...searchQuery, room_id: e.target.value})}
                            >
                                <option value="">全ての部屋タイプ</option>
                                {rooms.map((r: any) => <option key={r.id} value={r.id}>{r.name}</option>)}
                            </select>
                        </div>

                        {/* 日程 */}
                        <div className="flex items-center gap-3 border-l border-stone-600 pl-8">
                            <span className="opacity-70 font-bold">ご宿泊日程</span>
                            <div className="flex items-center gap-2">
                                <input type="date" className="bg-stone-700 border-stone-600 h-10 text-white rounded px-3 text-sm" value={searchQuery.checkin} onChange={e => handleCheckinChange(e.target.value)} />
                                <span className="mx-1">〜</span>
                                <input type="date" className="bg-stone-700 border-stone-600 h-10 text-white rounded px-3 text-sm" value={searchQuery.checkout} onChange={e => handleCheckoutChange(e.target.value)} />
                            </div>
                        </div>

                        {/* 人数・室数 */}
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

                        {/* ボタン類 */}
                        <div className="flex gap-3 ml-auto">
                            <button className="bg-stone-600 px-6 h-10 rounded hover:bg-stone-500 transition-colors font-medium" onClick={() => setSearchQuery({room_id: '', checkin: today, checkout: tomorrow, adults: 2, children: 0, room_count: 1})}>クリア</button>
                            <button className="bg-amber-700 px-10 h-10 rounded hover:bg-amber-600 transition-colors font-bold tracking-widest shadow-lg">再検索</button>
                        </div>
                    </div>
                </div>
            </div>

            {/* 2. メインコンテンツ*/}
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
                            {/* 左：画像 */}
                            <div className="lg:w-1/4 h-64 lg:h-auto overflow-hidden">
                                <img 
                                    src={group.plan_thumbnail}
                                    className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                                    alt={group.name} 
                                />
                            </div>

                            {/* 中：プラン詳細 */}
                            <div className="lg:w-2/5 p-8 border-r border-stone-100 flex flex-col justify-between">
                                <div>
                                    <h3 className="text-xl font-bold text-stone-800 mb-4 tracking-wider">{group.name}</h3>
                                    <p className="text-sm text-stone-600 leading-relaxed line-clamp-4">{group.description}</p>
                                </div>
                                <button onClick={() => setModalPlan(group)} className="self-start mt-6 text-[10px] tracking-[0.2em] border border-stone-300 px-6 py-2 hover:bg-stone-800 hover:text-white transition-all">
                                    プラン内容を詳しく見る
                                </button>
                            </div>

                            {/* 右：お部屋選択肢（全て表示） */}
                            <div className="flex flex-col gap-1">
                                {group.room_options.map((option: any) => (
                                    <div key={option.room_id} className="flex items-center justify-between bg-stone-50 p-4 rounded border border-stone-100">
                                        
                                        {/* 1. 部屋情報エリア*/}
                                        <div className="flex-1 min-w-0 pr-4">
                                            <p className="text-sm font-bold text-stone-800 truncate">
                                                {option.room_name}
                                            </p>
                                        </div>

                                        {/* 2. 価格エリア*/}
                                        <div className="w-32 text-right pr-6">
                                            <span className="text-xs text-stone-500">1名 </span>
                                            <span className="text-lg font-bold text-amber-900">
                                                ¥{option.price.toLocaleString()}
                                            </span>
                                        </div>

                                        {/* 3. ボタンエリア*/}
                                        <button 
                                            onClick={() => handleReserve(group.id, option.room_id)}
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

            {/* 3. モーダル（この部分を追加してください） */}
            {modalPlan && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-stone-900/80 backdrop-blur-sm" onClick={() => setModalPlan(null)}>
                    <div className="bg-white max-w-2xl w-full rounded-sm shadow-2xl relative overflow-hidden" onClick={e => e.stopPropagation()}>
                        <button onClick={() => setModalPlan(null)} className="absolute top-4 right-4 text-2xl text-stone-400 hover:text-stone-800 z-10">&times;</button>
                        {/* 修正：画像パスを配列から取得 */}
                        <img src={modalPlan.room_options[0].room_image} className="w-full h-64 object-cover" />
                        <div className="p-8">
                            <h2 className="text-2xl font-light tracking-widest mb-6 border-b pb-4">{modalPlan.name}</h2>
                            <p className="text-stone-600 leading-loose text-sm whitespace-pre-wrap">{modalPlan.description}</p>
                            <div className="mt-8 pt-6 border-t text-stone-400 text-[10px]">※ 当プランは大人1名様より承ります。季節によりお料理の内容が異なります。</div>
                        </div>
                    </div>
                </div>
            )}
        </GuestLayout>
    );
}
