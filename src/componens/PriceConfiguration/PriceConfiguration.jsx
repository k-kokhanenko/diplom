import {Header} from '../Header/Header';
import { useState, useEffect } from 'react';
import { useSelector } from 'react-redux';

export const PriceConfiguration = () => {   
    const halls = useSelector(state => state.halls);
    const [currentHall, setCurrentHall] = useState(() => {
        return localStorage.getItem("price-currentHall") || 0;
    });

    const [StandartPrice, setStandartPrice] = useState(0);
    const [VipPrice, setVipPrice] = useState(0);

    const getData = async (hall) => {
        const response = await fetch(`http://phpsitechecker.ru/prices/${hall}/`, {
          method : "GET",
        });
        const data = await response.json();
        //console.log(data);        
        setStandartPrice(data.price[0].standart_price)
        setVipPrice(data.price[0].vip_price);
    }

    const updatePrice = async () => {
        const response = await fetch(`http://phpsitechecker.ru/prices/`, {
            method : "UPDATE",
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: halls[currentHall].id, standart_price : StandartPrice, vip_price: VipPrice })
        });
        const data = await response.json();
    
        if (!data.result) {
            alert(`Ошибка выполнения запроса: ${data.message}`);
        } else {
            alert(data.message);      
        }  
      }

    const handleChangeActiveHall = (e) => {
        if (currentHall != e.target.value) {
            setCurrentHall(e.target.value);
            getData(halls[e.target.value].id);
        }        
    }

    const handleOnSave = (e) => {
        updatePrice();        
    }

    const showHallsItems = halls?.map(function(hall, index) {        
        return <li key={hall.id}>
            <input type="radio" className="conf-step__radio" name="prices-hall" value={index} onChange={handleChangeActiveHall} checked={currentHall == index}/>
            <span className="conf-step__selector">{hall.name}</span>
        </li>;
    });

    useEffect(() => {
        if (halls[currentHall] !== undefined) {
            getData(halls[currentHall].id);
        }
      }, [halls]);

    useEffect(() => {
        localStorage.setItem("price-currentHall", currentHall);
    }, [currentHall]);


    if (halls !== undefined  && halls.length > 0) {
        return (
            <>    
                <section className="conf-step">
                    <Header title='Конфигурация цен' active={false}/>
                    <div className="conf-step__wrapper">                
                        {halls?.length > 0 && (
                            <p className="conf-step__paragraph">Выберите зал для конфигурации:</p>                
                        )}
                        <ul className="conf-step__selectors-box">
                            {showHallsItems}
                        </ul>

                        <p className="conf-step__paragraph">Установите цены для типов кресел:</p>

                        <div className="conf-step__legend">
                            <label className="conf-step__label">Цена, рублей<input type="number" className="conf-step__input" value={StandartPrice} onChange={(e) => setStandartPrice(e.target.value)}/></label>
                            за <span className="conf-step__chair conf-step__chair_standart"></span> обычные кресла
                        </div>
                        <div className="conf-step__legend">
                            <label className="conf-step__label">Цена, рублей<input type="number" className="conf-step__input" value={VipPrice} onChange={(e) => setVipPrice(e.target.value)}/></label>
                            за <span className="conf-step__chair conf-step__chair_vip"></span> VIP кресла
                        </div>

                        <fieldset className="conf-step__buttons text-center">
                            <input type="submit" value="Сохранить" className="conf-step__button conf-step__button-accent" onClick={handleOnSave}/>
                        </fieldset> 
                    </div>
                </section>
            </>    
        )
    }
}
