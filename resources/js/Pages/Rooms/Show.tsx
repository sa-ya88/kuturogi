// resources/js/Pages/Rooms/Show.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';

import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, Autoplay, EffectFade } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/effect-fade';

interface Plan {
    id: number;
    name: string;
    price_per_person: number;
    description: string;
}

interface Room {
    id: number;
    name: string;
    description: string;
    images: string[];
    features: string[];
    plans: Plan[];
}

export default function Show({ room }: { room: Room }) {
    return (
        <GuestLayout>
            <Head title={room.name} />

            <section className="pt-32 pb-20 max-w-7xl mx-auto px-4">
                <Link href="/rooms" className="text-stone-500 hover:text-stone-800 mb-8 inline-block text-sm">
                    ← お部屋一覧に戻る
                </Link>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
                    {/* 左側：画像スライダー*/}
                    <div className="overflow-hidden rounded-sm shadow-xl hover:shadow-2xl transition-shadow duration-500 bg-stone-200">
                        <Swiper
                            modules={[Navigation, Pagination, Autoplay, EffectFade]}
                            effect="fade"
                            fadeEffect={{
                                crossFade: true // 画像が重なりながらふわっと入れ替わる
                            }}
                            speed={4000} // 切り替わるスピード
                            autoplay={{
                                delay: 5000, // 次の画像へ行くまでの待ち時間
                                disableOnInteraction: false,
                            }}
                            navigation
                            pagination={{ clickable: true }}
                            loop={true}
                            className="h-[400px] md:h-[600px]"
                        >
                            {room.images.map((image, index) => (
                                <SwiperSlide key={index}>
                                    <img 
                                        src={image} 
                                        alt={room.name} 
                                        className="w-full h-full object-cover" 
                                    />
                                </SwiperSlide>
                            ))}
                        </Swiper>
                    </div>

                    {/* 右側：情報 */}
                    <div className="flex flex-col">
                        <h1 className="text-4xl font-light tracking-widest mb-6">{room.name}</h1>
                        
                        <p className="text-stone-600 leading-loose mb-12 whitespace-pre-wrap">
                            {room.description}
                        </p>

                        {/* 部屋の詳細欄 */}
                        <div className="bg-stone-50 p-8 mb-12 border border-stone-100">
                            <h3 className="text-lg font-medium mb-6 pb-2 border-b border-stone-200 tracking-widest">お部屋詳細</h3>
                            <dl className="grid grid-cols-1 gap-y-4 text-sm">
                                <div className="flex border-b border-stone-100 pb-2">
                                    <dt className="w-24 text-stone-400">客室設備</dt>
                                    <dd className="text-stone-700">バス、シャワー、トイレ、冷暖房、テレビ、冷蔵庫</dd>
                                </div>
                                <div className="flex border-b border-stone-100 pb-2">
                                    <dt className="w-24 text-stone-400">インターネット</dt>
                                    <dd className="text-stone-700">全室Wi-Fi無料</dd>
                                </div>
                                <div className="flex border-b border-stone-100 pb-2">
                                    <dt className="w-24 text-stone-400">禁煙・喫煙</dt>
                                    <dd className="text-stone-700">全室禁煙（喫煙スペースあり）</dd>
                                </div>
                                <div className="flex">
                                    <dt className="w-24 text-stone-400">アメニティ</dt>
                                    <dd className="text-stone-700">タオル、歯ブラシ、浴衣、ドライヤー、石鹸類</dd>
                                </div>
                            </dl>
                        </div>

                        {/* 予約ボタン */}
                        <div className="border-t border-stone-200 pt-8">
                             <Link 
                                href={route('reservations.create', { room_id: room.id })} 
                                className="block w-full text-center bg-stone-800 text-white py-4 tracking-widest hover:bg-stone-700 transition">
                                    空室確認・予約に進む
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </GuestLayout>
    );
}