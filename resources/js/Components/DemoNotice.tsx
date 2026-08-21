import { FormEventHandler } from 'react';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

type Props = {
    onFillDummy?: FormEventHandler<HTMLButtonElement> | (() => void);
    fillLabel?: string;
};

export default function DemoNotice({ onFillDummy, fillLabel = 'ダミー情報を入力' }: Props) {
    const demo = usePage<PageProps>().props.demo;
    const hours = demo?.refreshHours ?? 4;

    return (
        <div className="mb-6 border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p className="font-medium tracking-wide">ポートフォリオ用の公開デモです</p>
            <ul className="mt-2 list-disc space-y-1 pl-5 text-amber-900/80 leading-relaxed">
                <li>本名・住所・電話番号・実在のカードは入力しないでください。</li>
                <li>決済はテストモードのため実際は行われません。</li>
                <li>データは{hours}時間ごとに初期化されます。</li>
            </ul>
            {onFillDummy && (
                <button
                    type="button"
                    onClick={onFillDummy}
                    className="mt-3 border border-amber-800 px-4 py-2 text-xs tracking-widest hover:bg-amber-100 transition"
                >
                    {fillLabel}
                </button>
            )}
        </div>
    );
}
