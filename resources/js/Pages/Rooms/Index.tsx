import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

// 型定義をしっかり反映
interface Room {
    id: number;
    name: string;
    description: string;
    features: string[];
    images: string[];
}

export default function Index({ rooms }: { rooms: Room[] }) {
    return (
        <GuestLayout>
            <Head title="お部屋一覧" />

            {/* ヒーローエリア */}
            <section className="pt-32 pb-16 bg-stone-100">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <h1 className="text-4xl font-light tracking-[0.2em] mb-4">お部屋</h1>
                    <p className="text-stone-500 tracking-widest">四季を愛で、時に浸る、寛ぎの空間。</p>
                </div>
            </section>

            {/* お部屋リスト */}
            <section className="py-20 max-w-7xl mx-auto px-4">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    {rooms.map((room) => (
                        <div key={room.id} className="group bg-white overflow-hidden shadow-sm hover:shadow-xl transition-shadow duration-300">
                            <div className="relative h-64 overflow-hidden">
                                {/* 画像は images配列の1枚目(index 0)を表示 */}
                                <img 
                                    src={room.images[0]} 
                                    alt={room.name} 
                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                            </div>
                            <div className="p-8">
                                <div className="flex flex-wrap gap-2 mb-4">
                                    {room.features.map((feature: string, index: number) => (
                                        <span key={index} className="text-[10px] border border-stone-300 px-2 py-1 text-stone-500 tracking-tighter">
                                            {feature}
                                        </span>
                                    ))}
                                </div>
                                <h2 className="text-xl font-medium mb-4 tracking-widest">{room.name}</h2>
                                <p className="text-stone-600 text-sm leading-relaxed mb-6">
                                    {room.description}
                                </p>
                                <Link 
                                    href={route('rooms.show', { room: room.id })}
                                    className="inline-block text-xs tracking-[0.2em] border-b border-stone-800 pb-1 hover:text-amber-800 hover:border-amber-800 transition-colors"
                                >
                                    詳細を見る
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>
            </section>
        </GuestLayout>
    );
}
