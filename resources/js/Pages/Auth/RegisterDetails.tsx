import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import DemoNotice from '@/Components/DemoNotice';
import { dummyRegisterDetails } from '@/data/demoDummy';

interface Props {
    email: string;
    token: string;
}

export default function RegisterDetails({ email, token }: Props) {
    // 💡 Laravel側のバリデーション（birthday, zip_code）に合わせてキー名を修正
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token || '',
        email: email || '',
        password: '',
        password_confirmation: '',
        name: '',
        name_kana: '',
        birthday: '', // birthdate から birthday に変更
        gender: '',
        zip_code: '', // postal_code から zip_code に変更
        address: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        // 本登録（データ保存）のエンドポイントへポスト
        post(route('register.complete'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const inputClassName =
        'mt-1 block w-full border-stone-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500';

    return (
        <GuestLayout>
            <Head title="会員情報入力" />

            <section className="pt-32 pb-16 bg-stone-100 text-center">
                <h1 className="text-4xl font-light tracking-widest">会員情報入力</h1>
                <p className="mt-4 text-stone-500 text-sm">
                    アカウントに登録する詳細情報を入力してください
                </p>
            </section>

            <section className="py-20 max-w-xl mx-auto px-4">
                <div className="bg-white border border-stone-200 p-8 shadow-sm">
                    <DemoNotice
                        onFillDummy={() => {
                            setData({
                                ...data,
                                ...dummyRegisterDetails,
                            });
                        }}
                    />
                    
                    {/* メールアドレスの確認表示（変更不可） */}
                    <div className="mb-6 p-4 bg-stone-50 border border-stone-100 rounded text-sm text-stone-600">
                        <span className="font-medium text-stone-700">登録メールアドレス:</span> {data.email}
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        {/* パスワード */}
                        <div>
                            <label htmlFor="password" className="block text-sm font-medium text-stone-700">
                                パスワード
                            </label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                required
                                onChange={(e) => setData('password', e.target.value)}
                                className={inputClassName}
                            />
                            {errors.password && (
                                <div className="text-red-600 text-sm mt-1">{errors.password}</div>
                            )}
                        </div>

                        {/* パスワード（確認） */}
                        <div>
                            <label htmlFor="password_confirmation" className="block text-sm font-medium text-stone-700">
                                パスワード（確認）
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                value={data.password_confirmation}
                                required
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className={inputClassName}
                            />
                            {errors.password_confirmation && (
                                <div className="text-red-600 text-sm mt-1">{errors.password_confirmation}</div>
                            )}
                        </div>

                        {/* お名前 */}
                        <div>
                            <label htmlFor="name" className="block text-sm font-medium text-stone-700">
                                お名前
                            </label>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                value={data.name}
                                required
                                placeholder="山田 太郎"
                                onChange={(e) => setData('name', e.target.value)}
                                className={inputClassName}
                            />
                            {errors.name && (
                                <div className="text-red-600 text-sm mt-1">{errors.name}</div>
                            )}
                        </div>

                        {/* お名前（かな） */}
                        <div>
                            <label htmlFor="name_kana" className="block text-sm font-medium text-stone-700">
                                お名前（かな）
                            </label>
                            <input
                                id="name_kana"
                                type="text"
                                name="name_kana"
                                value={data.name_kana}
                                required
                                placeholder="やまだ たろう"
                                onChange={(e) => setData('name_kana', e.target.value)}
                                className={inputClassName}
                            />
                            {errors.name_kana && (
                                <div className="text-red-600 text-sm mt-1">{errors.name_kana}</div>
                            )}
                        </div>

                        {/* 生年月日 */}
                        <div>
                            <label htmlFor="birthday" className="block text-sm font-medium text-stone-700">
                                生年月日
                            </label>
                            <input
                                id="birthday"
                                type="date"
                                name="birthday"
                                value={data.birthday}
                                required
                                onChange={(e) => setData('birthday', e.target.value)}
                                className={inputClassName}
                            />
                            {errors.birthday && (
                                <div className="text-red-600 text-sm mt-1">{errors.birthday}</div>
                            )}
                        </div>

                        {/* 性別 */}
                        <div>
                            <label htmlFor="gender" className="block text-sm font-medium text-stone-700">
                                性別
                            </label>
                            <select
                                id="gender"
                                name="gender"
                                value={data.gender}
                                required
                                onChange={(e) => setData('gender', e.target.value)}
                                className={inputClassName}
                            >
                                <option value="">選択してください</option>
                                <option value="male">男性</option>
                                <option value="female">女性</option>
                                <option value="other">その他</option>
                                <option value="prefer_not_to_say">回答しない</option>
                            </select>
                            {errors.gender && (
                                <div className="text-red-600 text-sm mt-1">{errors.gender}</div>
                            )}
                        </div>

                        {/* 郵便番号 */}
                        <div>
                            <label htmlFor="zip_code" className="block text-sm font-medium text-stone-700">
                                郵便番号
                            </label>
                            <input
                                id="zip_code"
                                type="text"
                                name="zip_code"
                                value={data.zip_code}
                                required
                                placeholder="1234567"
                                onChange={(e) => setData('zip_code', e.target.value)}
                                className={inputClassName}
                            />
                            {errors.zip_code && (
                                <div className="text-red-600 text-sm mt-1">{errors.zip_code}</div>
                            )}
                        </div>

                        {/* 住所 */}
                        <div>
                            <label htmlFor="address" className="block text-sm font-medium text-stone-700">
                                住所
                            </label>
                            <input
                                id="address"
                                type="text"
                                name="address"
                                value={data.address}
                                required
                                placeholder="東京都渋谷区〇〇 1-2-3"
                                onChange={(e) => setData('address', e.target.value)}
                                className={inputClassName}
                            />
                            {errors.address && (
                                <div className="text-red-600 text-sm mt-1">{errors.address}</div>
                            )}
                        </div>

                        {/* 送信ボタン */}
                        <div className="pt-4">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-stone-800 text-white py-3 tracking-widest hover:bg-stone-700 transition disabled:opacity-50"
                            >
                                {processing ? '登録中...' : '登録を完了する'}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </GuestLayout>
    );
}
