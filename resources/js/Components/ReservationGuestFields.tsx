import { dummyReservationGuest } from '@/data/demoDummy';
import DemoNotice from '@/Components/DemoNotice';
import { Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { PageProps } from '@/types';

export const reservationFieldId = (name: string) => `reservation-field-${name}`;

type GuestFormData = {
    last_name: string;
    first_name: string;
    last_name_kana: string;
    first_name_kana: string;
    tel: string;
    email: string;
    zip_code: string;
    address: string;
    building: string;
    email_magazine: boolean;
    register_membership: boolean;
    representatives: string[];
    room_count: number;
};

type MemberInfo = {
    name?: string;
    name_kana?: string;
    email?: string;
    zip_code?: string;
    address?: string;
} | null;

type Props = {
    data: GuestFormData;
    setData: (key: string, value: unknown) => void;
    validationErrors: Record<string, string>;
    isAuthenticated: boolean;
    member?: MemberInfo;
};

export default function ReservationGuestFields({
    data,
    setData,
    validationErrors,
    isAuthenticated,
    member = null,
}: Props) {
    const demo = usePage<PageProps>().props.demo;
    const allowRegistration = demo?.allowRegistration !== false;
    const demoEnabled = Boolean(demo?.enabled);

    useEffect(() => {
        if (!allowRegistration && data.register_membership) {
            setData('register_membership', false);
        }
    }, [allowRegistration, data.register_membership, setData]);

    const fillDummy = () => {
        Object.entries(dummyReservationGuest).forEach(([key, value]) => {
            setData(key, value);
        });
        const roomCount = Math.max(1, Number(data.room_count) || 1);
        const fullName = `${dummyReservationGuest.last_name} ${dummyReservationGuest.first_name}`;
        setData(
            'representatives',
            Array.from({ length: roomCount }, () => fullName),
        );
    };

    if (isAuthenticated) {
        return (
            <div className="bg-white border p-8 space-y-6">
                <div className="flex justify-between items-center border-b pb-4">
                    <h2 className="text-xl font-bold tracking-widest">お客様情報</h2>
                    <Link href={route('profile.edit')} className="text-xs text-amber-700 border border-amber-700 px-4 py-1 hover:bg-amber-50">
                        会員情報の確認・変更
                    </Link>
                </div>
                <p className="text-xs text-stone-500">ログイン中の会員情報を利用します。変更がある場合は会員情報から更新してください。</p>
                <div className="space-y-2 text-sm text-stone-700">
                    <p><span className="text-stone-500">お名前：</span>{member?.name || `${data.last_name} ${data.first_name}`.trim() || '—'}</p>
                    <p><span className="text-stone-500">フリガナ：</span>{member?.name_kana || `${data.last_name_kana} ${data.first_name_kana}`.trim() || '—'}</p>
                    <p><span className="text-stone-500">メール：</span>{member?.email || data.email || '—'}</p>
                    <p>
                        <span className="text-stone-500">住所：</span>
                        {`${member?.zip_code || data.zip_code || ''} ${member?.address || data.address || ''}`.trim() || '—'}
                    </p>
                </div>

                {Number(data.room_count) > 1 && (
                    <div className="space-y-4 border-t pt-6">
                        <h3 className="text-sm font-bold tracking-widest">各部屋の代表者名 <span className="text-red-600">*</span></h3>
                        <p className="text-xs text-stone-500">1室目は会員名を初期値にできます。2室目以降は各部屋の代表者名を入力してください。</p>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {Array.from({ length: Math.max(1, Number(data.room_count) || 1) }).map((_, index) => (
                                <div key={`representative-${index}`}>
                                    <label className="block text-xs text-stone-500 mb-1">{index + 1}室目</label>
                                    <input
                                        type="text"
                                        id={reservationFieldId(`representatives_${index}`)}
                                        className={`w-full border-stone-300 ${validationErrors[`representatives_${index}`] ? 'border-red-500' : ''}`}
                                        value={data.representatives?.[index] ?? ''}
                                        placeholder={index === 0 ? '未入力の場合は会員名を使用' : '代表者名'}
                                        onChange={(e) => {
                                            const next = [...(data.representatives ?? [])];
                                            next[index] = e.target.value;
                                            setData('representatives', next);
                                        }}
                                    />
                                    {validationErrors[`representatives_${index}`] && (
                                        <p className="text-red-600 text-xs mt-1">{validationErrors[`representatives_${index}`]}</p>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        );
    }

    return (
        <div className="bg-white border p-8 space-y-8">
            <div className="flex justify-between items-center border-b pb-4">
                <h2 className="text-xl font-bold tracking-widest">お客様情報の入力</h2>
                {!isAuthenticated && (
                    <Link href={route('login')} className="text-xs text-amber-700 border border-amber-700 px-4 py-1 hover:bg-amber-50">
                        会員の方はログイン
                    </Link>
                )}
            </div>

            <DemoNotice onFillDummy={fillDummy} />

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                    <label className="block text-sm font-bold">お名前（漢字） <span className="text-red-600">*</span></label>
                    <div className="flex gap-2">
                        <div className="w-full">
                            <input
                                type="text"
                                id={reservationFieldId('last_name')}
                                placeholder="姓"
                                className={`w-full border-stone-300 ${validationErrors.last_name ? 'border-red-500' : ''}`}
                                value={data.last_name}
                                onChange={(e) => setData('last_name', e.target.value)}
                            />
                            {validationErrors.last_name && (
                                <p className="text-red-600 text-xs mt-1">{validationErrors.last_name}</p>
                            )}
                        </div>
                        <div className="w-full">
                            <input
                                type="text"
                                id={reservationFieldId('first_name')}
                                placeholder="名"
                                className={`w-full border-stone-300 ${validationErrors.first_name ? 'border-red-500' : ''}`}
                                value={data.first_name}
                                onChange={(e) => setData('first_name', e.target.value)}
                            />
                            {validationErrors.first_name && (
                                <p className="text-red-600 text-xs mt-1">{validationErrors.first_name}</p>
                            )}
                        </div>
                    </div>
                </div>
                <div className="space-y-4">
                    <label className="block text-sm font-bold">お名前（ひらがな） <span className="text-red-600">*</span></label>
                    <div className="flex gap-2">
                        <div className="w-full">
                            <input
                                type="text"
                                id={reservationFieldId('last_name_kana')}
                                placeholder="せい"
                                className={`w-full border-stone-300 ${validationErrors.last_name_kana ? 'border-red-500' : ''}`}
                                value={data.last_name_kana}
                                onChange={(e) => setData('last_name_kana', e.target.value)}
                            />
                            {validationErrors.last_name_kana && (
                                <p className="text-red-600 text-xs mt-1">{validationErrors.last_name_kana}</p>
                            )}
                        </div>
                        <div className="w-full">
                            <input
                                type="text"
                                id={reservationFieldId('first_name_kana')}
                                placeholder="めい"
                                className={`w-full border-stone-300 ${validationErrors.first_name_kana ? 'border-red-500' : ''}`}
                                value={data.first_name_kana}
                                onChange={(e) => setData('first_name_kana', e.target.value)}
                            />
                            {validationErrors.first_name_kana && (
                                <p className="text-red-600 text-xs mt-1">{validationErrors.first_name_kana}</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {Number(data.room_count) > 1 && (
                <div className="space-y-4 border-t pt-6">
                    <h3 className="text-sm font-bold tracking-widest">各部屋の代表者名 <span className="text-red-600">*</span></h3>
                    <p className="text-xs text-stone-500">1室目は上記お名前を初期値にできます。2室目以降は各部屋の代表者名を入力してください。</p>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {Array.from({ length: Math.max(1, Number(data.room_count) || 1) }).map((_, index) => (
                            <div key={`representative-${index}`}>
                                <label className="block text-xs text-stone-500 mb-1">{index + 1}室目</label>
                                <input
                                    type="text"
                                    id={reservationFieldId(`representatives_${index}`)}
                                    className={`w-full border-stone-300 ${validationErrors[`representatives_${index}`] ? 'border-red-500' : ''}`}
                                    value={data.representatives?.[index] ?? ''}
                                    placeholder={index === 0 ? '未入力の場合は上記お名前を使用' : '代表者名'}
                                    onChange={(e) => {
                                        const next = [...(data.representatives ?? [])];
                                        next[index] = e.target.value;
                                        setData('representatives', next);
                                    }}
                                />
                                {validationErrors[`representatives_${index}`] && (
                                    <p className="text-red-600 text-xs mt-1">{validationErrors[`representatives_${index}`]}</p>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label className="block text-sm font-bold mb-2">電話番号 <span className="text-red-600">*</span></label>
                    <input
                        id={reservationFieldId('tel')}
                        type="tel"
                        placeholder="09012345678"
                        className={`w-full border-stone-300 ${validationErrors.tel ? 'border-red-500' : ''}`}
                        value={data.tel}
                        onChange={(e) => setData('tel', e.target.value)}
                    />
                    {validationErrors.tel && (
                        <p className="text-red-600 text-sm mt-2">{validationErrors.tel}</p>
                    )}
                </div>
                <div>
                    <label className="block text-sm font-bold mb-2">メールアドレス <span className="text-red-600">*</span></label>
                    <input
                        id={reservationFieldId('email')}
                        type="email"
                        placeholder="example@mail.com"
                        className={`w-full border-stone-300 ${validationErrors.email ? 'border-red-500' : ''}`}
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    {validationErrors.email && (
                        <p className="text-red-600 text-sm mt-2">{validationErrors.email}</p>
                    )}
                </div>
            </div>

            <div className="space-y-4">
                <label className="block text-sm font-bold">住所 <span className="text-red-600">*</span></label>
                <div>
                    <input
                        type="text"
                        id={reservationFieldId('zip_code')}
                        placeholder="郵便番号（ハイフンなし）"
                        className={`w-1/3 border-stone-300 block ${validationErrors.zip_code ? 'border-red-500' : ''}`}
                        value={data.zip_code}
                        onChange={(e) => setData('zip_code', e.target.value)}
                    />
                    {validationErrors.zip_code && (
                        <p className="text-red-600 text-sm mt-1">{validationErrors.zip_code}</p>
                    )}
                </div>
                <div>
                    <input
                        type="text"
                        id={reservationFieldId('address')}
                        placeholder="都道府県・市区町村・番地"
                        className={`w-full border-stone-300 ${validationErrors.address ? 'border-red-500' : ''}`}
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                    />
                    {validationErrors.address && (
                        <p className="text-red-600 text-sm mt-1">{validationErrors.address}</p>
                    )}
                </div>
                <input
                    type="text"
                    placeholder="建物名・部屋番号"
                    className="w-full border-stone-300"
                    value={data.building}
                    onChange={(e) => setData('building', e.target.value)}
                />
            </div>

            <div className="space-y-4 pt-4 border-t">
                <div>
                    <label className="flex items-center gap-3 text-sm cursor-pointer">
                        <input type="checkbox" checked={data.email_magazine} onChange={(e) => setData('email_magazine', e.target.checked)} className="text-stone-800 focus:ring-stone-800" />
                        お得な情報（メールマガジン）の配信を希望する
                    </label>
                    {demoEnabled && (
                        <p className="mt-1 ml-7 text-xs text-stone-500 leading-relaxed">
                            公開デモではメール配信は行いません。チェックしてもメールは送信されません。
                        </p>
                    )}
                </div>
                {!isAuthenticated && (
                    <div>
                        <label
                            className={`flex items-center gap-3 text-sm font-bold text-amber-900 ${
                                allowRegistration ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'
                            }`}
                        >
                            <input
                                type="checkbox"
                                checked={allowRegistration && data.register_membership}
                                disabled={!allowRegistration}
                                onChange={(e) => setData('register_membership', e.target.checked)}
                                className="text-amber-700 focus:ring-amber-700 disabled:opacity-50"
                            />
                            予約と同時に会員登録を行う
                        </label>
                        {!allowRegistration && (
                            <p className="mt-1 ml-7 text-xs text-stone-500 leading-relaxed">
                                公開デモでは新規会員登録を停止しています。会員機能の確認はログイン画面のゲスト（テストユーザー）をご利用ください。
                            </p>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
