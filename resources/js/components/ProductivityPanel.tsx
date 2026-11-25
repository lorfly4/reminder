import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend } from 'chart.js';
import { Line } from 'react-chartjs-2';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend);

export function ProductivityPanel({ labels, completed }: { labels: string[]; completed: number[] }) {
    return (
        <Line
            data={{
                labels,
                datasets: [{ label: 'Selesai', data: completed, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,.12)', tension: 0.35 }],
            }}
            options={{ responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }}
        />
    );
}
