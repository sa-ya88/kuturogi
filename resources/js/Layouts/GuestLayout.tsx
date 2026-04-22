// resources/js/Layouts/GuestLayout.tsx
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-[#f8f5f0] text-gray-800 font-serif">
            {/* ヘッダー */}
            <nav className="fixed w-full z-50 bg-white/80 backdrop-blur-sm border-b border-gray-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-20 items-center">
                        <Link href="/" className="text-2xl font-bold tracking-widest">
                            山彦旅館 KUTUROGI
                        </Link>
                        <div className="hidden md:flex space-x-8 text-sm tracking-widest">
                            <Link href="/rooms" className="hover:text-amber-700">お部屋</Link>
                            <Link href="/onsen" className="hover:text-amber-700">大浴場</Link>
                            <Link href="/food" className="hover:text-amber-700">お料理</Link>
                            <Link href="/access" className="hover:text-amber-700">アクセス</Link>
                        </div>
                    </div>
                </div>
            </nav>

            {/* 各ページの中身 */}
            <main>{children}</main>

            {/* フッター */}
            <footer className="bg-stone-800 text-white py-12 mt-20">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <p className="text-xl tracking-widest mb-4">くつろぎの宿</p>
                    <p className="text-sm text-stone-400">© 2026 Kuturogi Inn. All rights reserved.</p>
                </div>
            </footer>
        </div>
    );
}
