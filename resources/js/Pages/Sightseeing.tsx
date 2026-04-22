// resources/js/Pages/Sightseeing.tsx
import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

const spots = [
    { title: '古の寺院', desc: '徒歩10分。四季折々の庭園が美しい名刹です。', img: 'https://unsplash.com' },
    { title: '清流の滝', desc: '車で15分。マイナスイオン溢れる癒しのスポット。', img: 'https://unsplash.com' },
];

export default function Sightseeing() {
    return (
        <GuestLayout>
            <Head title="周辺観光" />
            <section className="pt-32 pb-16 bg-stone-100 text-center">
                <h1 className="text-4xl font-light tracking-widest">周辺観光</h1>
            </section>
            <section className="py-20 max-w-7xl mx-auto px-4 grid md:grid-cols-2 gap-12">
                {spots.map((spot, i) => (
                    <div key={i} className="space-y-4">
                        <img src={spot.img} className="w-full h-80 object-cover shadow-md" />
                        <h2 className="text-xl tracking-wider">{spot.title}</h2>
                        <p className="text-stone-600 leading-relaxed">{spot.desc}</p>
                    </div>
                ))}
            </section>
        </GuestLayout>
    );
}
