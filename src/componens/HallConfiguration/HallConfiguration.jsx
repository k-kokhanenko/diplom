import {Header} from '../Header/Header';
import { useSelector, useDispatch } from 'react-redux';
import { useState, useEffect } from 'react';
//import { tab } from '@testing-library/user-event/dist/tab';

export const HallConfiguration = (props) => {   
    const halls = useSelector(state => state.halls);
    const {maxRow, maxColumn} = props;
    const dispatch = useDispatch();

    const [currentHall, setCurrentHall] = useState(() => {
        return localStorage.getItem("hall-currentHall") || 0;
    });
    const [row, setRow] = useState(0);
    const [column, setColumn] = useState(0);
    const [seats, setSeats] = useState([]);

    const getData = async (hall) => {
        const response = await fetch(`http://phpsitechecker.ru/seats/${hall}/`, {
          method : "GET",
        });
        const data = await response.json();
        
        setRow(halls[currentHall].row);
        setColumn(halls[currentHall].column);
        setSeats(data.seats);

        //setRow(Object.keys(seats).length);
        //setColumn(Object.keys(seats).length);

        //console.log(Object.values(seats).length);        
    }

    const setData = async (hall, seats) => {
        const response = await fetch(`http://phpsitechecker.ru/seats/${hall}/`, {
          method : "POST",
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ row, column, seats })       
        });
        const data = await response.json();
        if (data.result == true) {
            alert(data.message);

            // Обновляем кол-во строк и столбцов в кинозале
            let copy = Object.assign([], halls);
            copy[currentHall].row = row;
            copy[currentHall].column = column;
            dispatch({type: "UPDATE", payload : copy});
        }
    }

    const handleChangeActiveHall = (e) => {
        if (currentHall != e.target.value) {
            setCurrentHall(e.target.value);
            getData(halls[e.target.value].id);
        }        
    }

    const handleOnCancel = (e) => {
        getData(halls[currentHall].id);
    }

    const handleOnSave = (e) => {        
        setData(halls[currentHall].id, seats);
    }

    const handleOnChangeRow = (e) => {
        if (e.target.value < 1 || e.target.value > maxRow) {
            alert(`Кол-во рядов должно быть в диапазоне 1-${maxRow}`);
            return;
        }

        let copy = Object.assign([], seats);
        if (e.target.value > row) {
            let diff = e.target.value - row;
            for (let i = 0; i < diff; i++) {
                let cell = {};
                for (let j = 1; j <= column; j++) {
                    cell[j] = 1;
                }
                copy.push(cell);
            }           
        } else {
            let diff = row - e.target.value;
            copy.splice(0-diff);
        }

        setSeats(copy);
        setRow(Number(e.target.value));

        //copy = Object.assign([], halls);
        //copy[currentHall].row = Number(e.target.value);
        //dispatch({type: "UPDATE", payload : copy});
    }

    const handleOnChangeColumn = (e) => {        
        if (e.target.value < 1 || e.target.value > maxColumn) {
            alert(`Кол-во мест должно быть в диапазоне 1-${maxColumn}`);
            return;
        }

        console.log(seats);

        let copy = Object.assign([], seats);
        if (e.target.value > column) {
            let diff = e.target.value - column;
            for (let i = 1; i <= row; i++) {
                for (let j = 1; j <= diff; j++) {
                    copy[i][column+j] = 1;
                }
            }
        } else {
            let diff = column - e.target.value;
            for (let i = 1; i <= row; i++) {
                for (let j = column; j > e.target.value; j--) {
                    delete copy[i][j];
                }
            }
        }

        setSeats(copy);
        setColumn(Number(e.target.value));     
    }

    const showHallsItems = halls?.map(function(hall, index) {        
        return <li key={hall.id}>
            <input type="radio" className="conf-step__radio" name="chairs-hall" value={index} onChange={handleChangeActiveHall} checked={currentHall == index}/>
            <span className="conf-step__selector">{hall.name}</span>
        </li>;
    });     

    useEffect(() => {
        localStorage.setItem("hall-currentHall", currentHall);

        if (halls[currentHall] !== undefined) {
            getData(halls[currentHall].id);
        }
    }, [halls, currentHall]);    

    if (halls !== undefined  && halls.length > 0) {
        return (
            <>    
                <section className="conf-step">
                    <Header title='Конфигурация залов' active={false}/>
                    <div className="conf-step__wrapper">                
                        {halls?.length > 0 && (
                            <p className="conf-step__paragraph">Выберите зал для конфигурации:</p>                
                        )}
                        <ul className="conf-step__selectors-box">
                            {showHallsItems}
                        </ul>
    
                        <p className="conf-step__paragraph">Укажите количество рядов и максимальное количество кресел в ряду:</p>
                        <div className="conf-step__legend">
                            <label className="conf-step__label">Рядов, шт<input type="number" className="conf-step__input" value={row} onChange={handleOnChangeRow}/></label>
                            <span className="multiplier">x</span>
                            <label className="conf-step__label">Мест, шт<input type="number" className="conf-step__input" value={column} onChange={handleOnChangeColumn}/></label>
                        </div>
    
                        <p className="conf-step__paragraph">Теперь вы можете указать типы кресел на схеме зала:</p>
                        <div className="conf-step__legend">
                            <span className="conf-step__chair conf-step__chair_standart"></span> — обычные кресла
                            <span className="conf-step__chair conf-step__chair_vip"></span> — VIP кресла
                            <span className="conf-step__chair conf-step__chair_disabled"></span> — заблокированные (нет кресла)
                            <p className="conf-step__hint">Чтобы изменить вид кресла, нажмите по нему левой кнопкой мыши</p>
                        </div> 

                        <div className="conf-step__hall">
                            <div className="conf-step__hall-wrapper">
                                {Object.entries(seats).map(([rowIndex, row]) => (
                                    <div key={rowIndex} className="step__row"> 
                                    {
                                        Object.entries(row).map(([cellIndex, cell]) => <>
                                        <span 
                                            key={`${rowIndex}-${cellIndex}`} 
                                            className={`conf-step__chair ${cell === 1 ? 'step__chair_standart' : cell === 2 ? 'conf-step__chair_vip' : 'conf-step__chair_disabled'}`}
                                            onClick={(e) => { 
                                                let newCell = cell + 1;
                                                if (newCell > 3) {
                                                    newCell = 1;
                                                }

                                                let copy = Object.assign([], seats);
                                                copy[rowIndex][cellIndex] = newCell;                                                                                                    
                                                setSeats(copy);
                                                }
                                            }
                                            >
                                        </span>
                                        </>)   
                                    }
                                    </div>
                                ))}
                            </div>
                        </div>

                        <fieldset className="conf-step__buttons text-center">
                            <button className="conf-step__button conf-step__button-regular" onClick={handleOnCancel}>Отмена</button>
                            <input type="submit" value="Сохранить" className="conf-step__button conf-step__button-accent" onClick={handleOnSave}/>
                        </fieldset>                         
                    </div>
                </section>
            </>    
        )    
    } 
}
