// resources/js/Layouts/GuestLayout.tsx
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function GuestLayout({ children }: PropsWithChildren) {
    const { auth, demo } = usePage<PageProps>().props;
    const user = auth.user;

    const navLinkClass = 'hover:text-amber-700 transition-colors';
    const authLinkClass = 'hover:text-amber-700 transition-colors';

    return (
        <div className="min-h-screen bg-[#f8f5f0] text-gray-800 font-serif">
            {/* ヘッダー */}
            <nav className="fixed w-full z-50 bg-white/80 backdrop-blur-sm border-b border-gray-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* 1段目: 会員メニュー */}
                    <div className="flex justify-between items-center h-10 border-b border-gray-100 text-xs tracking-widest gap-4">
                        {demo?.enabled ? (
                            <p className="text-[10px] sm:text-xs text-amber-800 leading-tight">
                                公開デモです。個人情報は入力しないでください。新規会員登録はできません。データは{demo.refreshHours ?? 4}時間ごとに初期化されます。
                            </p>
                        ) : (
                            <span />
                        )}
                        {!user ? (
                            <div className="flex items-center gap-4 shrink-0">
                                <Link href={route('register')} className={authLinkClass}>
                                    新規登録
                                </Link>
                                <Link href={route('login')} className={authLinkClass}>
                                    ログイン
                                </Link>
                            </div>
                        ) : (
                            <div className="flex items-center gap-4 shrink-0">
                                <span className="text-stone-600">ようこそ、{user.name}様</span>
                                <Link href={route('profile.edit')} className={authLinkClass}>
                                    会員情報確認・変更
                                </Link>
                                <Link href={route('reservations.index')} className={authLinkClass}>
                                    予約確認
                                </Link>
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className={authLinkClass}
                                >
                                    ログアウト
                                </Link>
                            </div>
                        )}
                    </div>

                    {/* 2段目: サイトメニュー */}
                    <div className="flex justify-between h-16 items-center gap-4">
                        <Link href="/" className="text-xl font-bold tracking-widest">
                            山彦旅館 KUTUROGI
                        </Link>
                        <div className="flex items-center gap-4 sm:gap-8">
                            <div className="hidden md:flex space-x-8 text-sm tracking-widest">
                                <Link href="/rooms" className={navLinkClass}>お部屋</Link>
                                <Link href="/onsen" className={navLinkClass}>大浴場</Link>
                                <Link href="/food" className={navLinkClass}>お料理</Link>
                                <Link href="/sightseeing" className={navLinkClass}>周辺観光</Link>
                                <Link href="/access" className={navLinkClass}>アクセス</Link>
                            </div>
                            <Link
                                href={route('reservations.create', {
                                    adults: 2,
                                    children: 0,
                                    room_count: 1,
                                })}
                                preserveState={false}
                                className="bg-amber-800 px-4 py-2 text-xs font-bold tracking-[0.2em] text-white shadow-sm transition-colors hover:bg-amber-700 sm:px-6 sm:text-sm"
                            >
                                宿泊予約
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            {/* 各ページの中身 */}
            <main>{children}</main>

            {/* フッター */}
            <footer className="bg-stone-800 text-white py-12 mt-20">
                <div className="max-w-7xl mx-auto px-4">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                        {/* 施設情報 */}
                        <div>
                            <h4 className="text-sm font-bold tracking-widest mb-4">くつろぎの宿</h4>
                            <p className="text-xs text-stone-400">山彦旅館 KUTUROGI</p>
                            <p className="text-xs text-stone-400 mt-2">心の癒しと安らぎをお届けする</p>
                        </div>

                        {/* 施設情報リンク */}
                        <div>
                            <h4 className="text-sm font-bold tracking-widest mb-4">施設情報</h4>
                            <ul className="space-y-2 text-xs">
                                <li><Link href="/rooms" className="text-stone-400 hover:text-white transition">お部屋</Link></li>
                                <li><Link href="/onsen" className="text-stone-400 hover:text-white transition">大浴場</Link></li>
                                <li><Link href="/food" className="text-stone-400 hover:text-white transition">お料理</Link></li>
                                <li><Link href="/sightseeing" className="text-stone-400 hover:text-white transition">周辺観光</Link></li>
                            </ul>
                        </div>

                        {/* サポート */}
                        <div>
                            <h4 className="text-sm font-bold tracking-widest mb-4">サポート</h4>
                            <ul className="space-y-2 text-xs">
                                <li><Link href="/contact" className="text-stone-400 hover:text-white transition">お問い合わせ</Link></li>
                                <li><Link href="/faq" className="text-stone-400 hover:text-white transition">よくあるご質問</Link></li>
                                <li><Link href="/access" className="text-stone-400 hover:text-white transition">アクセス</Link></li>
                            </ul>
                        </div>

                        {/* 会社情報 */}
                        <div>
                            <h4 className="text-sm font-bold tracking-widest mb-4">会社情報</h4>
                            <ul className="space-y-2 text-xs">
                                <li><Link href="/company" className="text-stone-400 hover:text-white transition">会社情報</Link></li>
                                <li><Link href="/news" className="text-stone-400 hover:text-white transition">ニュース</Link></li>
                            </ul>
                        </div>
                    </div>

                    <div className="border-t border-stone-700 pt-8 text-center">
                        <p className="text-sm text-stone-400 mb-2">© 2026 Kuturogi Inn. All rights reserved.</p>
                        <p className="text-xs text-stone-500">山彦旅館くつろぎ | 心の癒しと安らぎをお届けします</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}
