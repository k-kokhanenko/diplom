import {Header} from '../Header/Header';
import { useSelector } from 'react-redux';

export const OpenSales = () => {   
    const halls = useSelector(state => state.halls);
    const openHalls = async () => {
        const response = await fetch(`http://phpsitechecker.ru/halls/`, {
          method : "UPDATE",
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ active: true })
        });
        const data = await response.json();
        if (!data.result) {
            alert(`Ошибка выполнения запроса: ${data.message}`);
        } else {
            alert('Продажа открыта.');
        }  
    }
    
    const handleOpenSale = () => {
        openHalls();
    }

    if (halls !== undefined  && halls.length > 0) {
        return (
            <>    
                <section className="conf-step">
                    <Header title='Открыть подажи' active={false}/>
                    <div className="conf-step__wrapper text-center">
                        <p className="conf-step__paragraph">Всё готово, теперь можно:</p>
                        <button className="conf-step__button conf-step__button-accent" onClick={handleOpenSale}>Открыть продажу билетов</button>
                    </div>
                </section>

            </>    
        )
    }
}