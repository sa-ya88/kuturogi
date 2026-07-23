import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Props {
    mustVerifyEmail?: boolean;
    status?: string;
}

export default function Edit({ mustVerifyEmail, status }: Props) {
    // Laravelのコントローラーからログイン中のユーザーデータをProps経由で取得
    const user = usePage().props.auth.user as any;

    // 編集フォームの初期値に現在のユーザー情報をセット
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        name: user.name || '',
        name_kana: user.name_kana || '',
        email: user.email || '',
        birthday: user.birthday || '',
        gender: user.gender || '',
        zip_code: user.zip_code || '',
        address: user.address || '',
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        // Laravelの profile.update ルートへPATCH送信（標準の保存処理をそのまま活用）
        patch(route('profile.update'), {
            preserveScroll: true,
            onSuccess: () => {
                // パスワード入力欄だけをリセット
                setData((prev) => ({
                    ...prev,
                    current_password: '',
                    password: '',
                    password_confirmation: '',
                }));
            },
        });
    };

    const inputClassName =
        'mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 font-sans';

    return (
        <GuestLayout>
            <Head title="会員情報確認・変更" />
            <section className="pt-32 pb-16 bg-[#2d2a26] text-white">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">会員情報確認・変更</h1>
                    <p className="text-stone-400 tracking-widest text-sm md:text-base">ご登録いただいている会員情報の確認および変更が可能です</p>
                </div>
            </section>

            <section className="py-20 max-w-2xl mx-auto px-4">
                <div className="bg-white border border-stone-200 p-8 shadow-sm">
                    
                    {/* 保存完了時のトーストメッセージ */}
                    {recentlySuccessful && (
                        <div className="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm text-center rounded tracking-wider">
                            会員情報を更新いたしました。
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-8">
                        
                        {/* 基本情報セクション */}
                        <div className="space-y-6">
                            <h3 className="text-lg font-medium border-b border-stone-100 pb-2 tracking-wide text-stone-800">基本情報</h3>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-stone-700">お名前</label>
                                    <input id="name" type="text" value={data.name} required onChange={(e) => setData('name', e.target.value)} className={inputClassName} />
                                    {errors.name && <div className="text-red-600 text-sm mt-1">{errors.name}</div>}
                                </div>

                                <div>
                                    <label htmlFor="name_kana" className="block text-sm font-medium text-stone-700">お名前（かな）</label>
                                    <input id="name_kana" type="text" value={data.name_kana} required onChange={(e) => setData('name_kana', e.target.value)} className={inputClassName} />
                                    {errors.name_kana && <div className="text-red-600 text-sm mt-1">{errors.name_kana}</div>}
                                </div>
                            </div>

                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-stone-700">メールアドレス</label>
                                <input id="email" type="email" value={data.email} required onChange={(e) => setData('email', e.target.value)} className={inputClassName} />
                                {errors.email && <div className="text-red-600 text-sm mt-1">{errors.email}</div>}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="birthday" className="block text-sm font-medium text-stone-700">生年月日</label>
                                    <input id="birthday" type="date" value={data.birthday} required onChange={(e) => setData('birthday', e.target.value)} className={inputClassName} />
                                    {errors.birthday && <div className="text-red-600 text-sm mt-1">{errors.birthday}</div>}
                                </div>

                                <div>
                                    <label htmlFor="gender" className="block text-sm font-medium text-stone-700">性別</label>
                                    <select id="gender" value={data.gender} required onChange={(e) => setData('gender', e.target.value)} className={inputClassName}>
                                        <option value="">選択してください</option>
                                        <option value="male">男性</option>
                                        <option value="female">女性</option>
                                        <option value="other">その他</option>
                                        <option value="prefer_not_to_say">回答しない</option>
                                    </select>
                                    {errors.gender && <div className="text-red-600 text-sm mt-1">{errors.gender}</div>}
                                </div>
                            </div>
                        </div>

                        {/* 住所情報セクション */}
                        <div className="space-y-6 pt-4">
                            <h3 className="text-lg font-medium border-b border-stone-100 pb-2 tracking-wide text-stone-800">ご連絡先・住所</h3>
                            
                            <div>
                                <label htmlFor="zip_code" className="block text-sm font-medium text-stone-700">郵便番号</label>
                                <input id="zip_code" type="text" value={data.zip_code} required placeholder="1234567" onChange={(e) => setData('zip_code', e.target.value)} className={inputClassName} />
                                {errors.zip_code && <div className="text-red-600 text-sm mt-1">{errors.zip_code}</div>}
                            </div>

                            <div>
                                <label htmlFor="address" className="block text-sm font-medium text-stone-700">住所</label>
                                <input id="address" type="text" value={data.address} required onChange={(e) => setData('address', e.target.value)} className={inputClassName} />
                                {errors.address && <div className="text-red-600 text-sm mt-1">{errors.address}</div>}
                            </div>
                        </div>

                        {/* セキュリティセクション（必要な場合のみ入力） */}
                        <div className="space-y-6 pt-4">
                            <h3 className="text-lg font-medium border-b border-stone-100 pb-2 tracking-wide text-stone-800">パスワードの変更</h3>
                            <p className="text-xs text-stone-500 font-serif">※パスワードを変更する場合のみご入力ください。</p>
                            
                            <div>
                                <label htmlFor="current_password" className="block text-sm font-medium text-stone-700">現在のパスワード</label>
                                <input id="current_password" type="password" value={data.current_password} onChange={(e) => setData('current_password', e.target.value)} className={inputClassName} />
                                {errors.current_password && <div className="text-red-600 text-sm mt-1">{errors.current_password}</div>}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="password" className="block text-sm font-medium text-stone-700">新しいパスワード</label>
                                    <input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className={inputClassName} />
                                    {errors.password && <div className="text-red-600 text-sm mt-1">{errors.password}</div>}
                                </div>

                                <div>
                                    <label htmlFor="password_confirmation" className="block text-sm font-medium text-stone-700">新しいパスワード（確認）</label>
                                    <input id="password_confirmation" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} className={inputClassName} />
                                    {errors.password_confirmation && <div className="text-red-600 text-sm mt-1">{errors.password_confirmation}</div>}
                                </div>
                            </div>
                        </div>

                        {/* 更新ボタン */}
                        <div className="pt-6 border-t border-stone-100">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-stone-800 text-white py-3 tracking-widest hover:bg-stone-700 transition disabled:opacity-50 font-serif"
                            >
                                {processing ? '更新中...' : '会員情報を更新する'}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </GuestLayout>
    );
}