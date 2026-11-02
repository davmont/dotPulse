<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Spanish strings of the project tips (feedback.class.php, header_widget.php), keyed by the English text.
return array(
	'Project tips' => 'Consejos del proyecto',
	'unread' => 'sin leer',
	'No tips for this project.' => 'No hay consejos para este proyecto.',
	'Mark as read' => 'Marcar como leído',
	'Mark as unread' => 'Marcar como no leído',
	'Integration' => 'Integración',
	'Scope' => 'Alcance',
	'Schedule' => 'Cronograma',
	'Cost' => 'Costes',
	'Resources' => 'Recursos',
	'Risk' => 'Riesgos',

	'The project has no charter.' => 'El proyecto no tiene acta de constitución.',
	'No project charter is linked to this project. The charter states why the project exists, its objectives, high-level budget, milestones and success criteria, and it is what authorizes the project manager to use the organization\'s resources. Write one in the Initiating module and link it to the project.'
		=> 'Ningún acta de constitución está vinculada a este proyecto. El acta explica por qué existe el proyecto, sus objetivos, el presupuesto general, los hitos y los criterios de éxito, y es lo que autoriza al director del proyecto a usar los recursos de la organización. Redáctela en el módulo Inicio y vincúlela al proyecto.',
	'The project charter is not authorized yet.' => 'El acta de constitución aún no está autorizada.',
	'The project has a charter, but it has not been authorized. Until the sponsor authorizes it, the project is not formally started and the project manager has no authority to commit resources. Complete the charter and have it approved and authorized.'
		=> 'El proyecto tiene acta de constitución, pero no está autorizada. Hasta que el patrocinador la autorice, el proyecto no ha comenzado formalmente y el director del proyecto no tiene autoridad para comprometer recursos. Complete el acta y haga que la aprueben y autoricen.',
	'The project ends after the date in its charter.' => 'El proyecto termina después de la fecha de su acta.',
	'The project\'s end date is later than the end date of its authorized charter. Either the schedule no longer meets what was agreed with the sponsor, or the charter is out of date: agree a new date and record the change, rather than letting the two drift apart.'
		=> 'La fecha de fin del proyecto es posterior a la del acta autorizada. O el cronograma ya no cumple lo acordado con el patrocinador, o el acta está desactualizada: acuerde una nueva fecha y registre el cambio, en lugar de dejar que ambas se separen.',

	'The WBS has only a few items.' => 'La EDT tiene muy pocos elementos.',
	'The work breakdown structure has three items or fewer. A WBS this small rarely covers all of the deliverables and the project management work. Break the main deliverables down until each work package can be estimated, assigned and checked on its own.'
		=> 'La estructura de desglose del trabajo tiene tres elementos o menos. Una EDT tan pequeña rara vez cubre todos los entregables y el trabajo de dirección del proyecto. Descomponga los entregables principales hasta que cada paquete de trabajo se pueda estimar, asignar y comprobar por separado.',
	'The WBS has many items: check how it is broken down.' => 'La EDT tiene muchos elementos: revise cómo está descompuesta.',
	'The work breakdown structure has more than ten items. That is fine for a large project, but check that every item is a deliverable (a noun, not an action) and that work packages are not broken down into activities: activities belong in the schedule, under their work package.'
		=> 'La estructura de desglose del trabajo tiene más de diez elementos. Es normal en un proyecto grande, pero compruebe que cada elemento es un entregable (un sustantivo, no una acción) y que los paquetes de trabajo no se descomponen en actividades: las actividades van en el cronograma, bajo su paquete de trabajo.',
	'Some work packages have no size estimate.' => 'Algunos paquetes de trabajo no tienen el tamaño estimado.',
	'Activities are planned, but some work packages have no size. Estimate the size of each work package (pages, screens, components, ...) before the effort of its activities: the size is what the effort and duration estimates are checked against.'
		=> 'Hay actividades planificadas, pero algunos paquetes de trabajo no tienen tamaño. Estime el tamaño de cada paquete de trabajo (páginas, pantallas, componentes, ...) antes que el esfuerzo de sus actividades: el tamaño es la referencia con la que se comprueban las estimaciones de esfuerzo y duración.',
	'Some work package sizes are in hours or days.' => 'Algunos tamaños de paquetes de trabajo están en horas o días.',
	'Some work packages have their size in hours or days. Size measures the product (pages, features, function points, ...); hours and days measure the effort and duration of the activities. Use a unit of the product for the size.'
		=> 'Algunos paquetes de trabajo tienen el tamaño en horas o días. El tamaño mide el producto (páginas, funcionalidades, puntos de función, ...); las horas y los días miden el esfuerzo y la duración de las actividades. Use una unidad del producto para el tamaño.',
	'Two activities depend on each other.' => 'Dos actividades dependen la una de la otra.',
	'There is a circular dependency: two activities are each set as the predecessor of the other, so neither can start before the other finishes and the schedule cannot be worked out. Remove one of the two dependencies.'
		=> 'Hay una dependencia circular: dos actividades son cada una predecesora de la otra, así que ninguna puede empezar antes de que termine la otra y el cronograma no se puede calcular. Elimine una de las dos dependencias.',
	'A work package has a single activity.' => 'Un paquete de trabajo tiene una sola actividad.',
	'At least one work package is done by a single activity, so the activity and the work package are the same thing. Either list the activities needed to produce the work package, or check whether the work package is too small to be one.'
		=> 'Al menos un paquete de trabajo se realiza con una sola actividad, de modo que la actividad y el paquete de trabajo son lo mismo. Enumere las actividades necesarias para producir el paquete de trabajo, o compruebe si el paquete es demasiado pequeño para serlo.',
	'Some activities last more than two weeks.' => 'Algunas actividades duran más de dos semanas.',
	'At least one activity has an estimated duration of more than 14 days. Long activities are hard to monitor, because progress only shows when they finish. Split them into shorter activities, each with a result that can be checked.'
		=> 'Al menos una actividad tiene una duración estimada de más de 14 días. Las actividades largas son difíciles de seguir, porque el avance solo se ve cuando terminan. Divídalas en actividades más cortas, cada una con un resultado que se pueda comprobar.',
	'Some activities take 8 hours of effort or less.' => 'Algunas actividades tienen un esfuerzo de 8 horas o menos.',
	'At least one activity is estimated at one working day or less. Many very small activities make the schedule long and hard to keep up to date: consider merging them with related activities of the same work package.'
		=> 'Al menos una actividad está estimada en una jornada de trabajo o menos. Muchas actividades muy pequeñas hacen el cronograma largo y difícil de mantener al día: considere unirlas con actividades relacionadas del mismo paquete de trabajo.',
	'Some activities take 80 hours of effort or more.' => 'Algunas actividades tienen un esfuerzo de 80 horas o más.',
	'At least one activity is estimated at 80 hours (two working weeks) or more. Estimates this large are usually less accurate and hard to track: break the activity down into smaller ones.'
		=> 'Al menos una actividad está estimada en 80 horas (dos semanas de trabajo) o más. Las estimaciones tan grandes suelen ser menos precisas y difíciles de seguir: descomponga la actividad en otras más pequeñas.',
	'The work done costs more than planned (CPI below 0.9).' => 'El trabajo hecho cuesta más de lo previsto (CPI por debajo de 0,9).',
	'Up to today the actual cost of the hours logged is higher than the earned value of the work done: each unit spent has produced less than 0.9 of planned work. See the trend and the activities behind it on the Cost tab (monitoring and control), then review the estimates of the remaining work or the budget.'
		=> 'Hasta hoy, el coste real de las horas registradas supera el valor ganado del trabajo hecho: cada unidad gastada ha producido menos de 0,9 de trabajo planificado. Mira la tendencia y las actividades que la causan en la pestaña Coste (monitorización y control) y revisa las estimaciones del trabajo pendiente o el presupuesto.',
	'The project is behind schedule (SPI below 0.9).' => 'El proyecto va retrasado (SPI por debajo de 0,9).',
	'Up to today the work done is worth less than 90 % of the work planned for this date (earned value against planned value). Find the late activities on the Schedule tab (monitoring and control) and update the plan, the scope or the people allocated.'
		=> 'Hasta hoy, el trabajo hecho vale menos del 90 % del trabajo planificado para esta fecha (valor ganado frente a valor planificado). Busca las actividades retrasadas en la pestaña Cronograma (monitorización y control) y actualiza el plan, el alcance o las personas asignadas.',
	'There are risks but no contingency reserve.' => 'Hay riesgos pero no hay reserva para contingencias.',
	'The project has risks, but its budget has no contingency reserve. Set aside a reserve for the risks that you accept or that remain after their responses, based on their probability and impact (Budget tab of the project).'
		=> 'El proyecto tiene riesgos, pero su presupuesto no tiene reserva para contingencias. Reserve una cantidad para los riesgos que acepta o que quedan tras sus respuestas, según su probabilidad e impacto (pestaña Presupuesto del proyecto).',
	'There is a contingency reserve but no management reserve.' => 'Hay reserva para contingencias pero no reserva de gestión.',
	'The budget has a contingency reserve for the known risks, but its management reserve is zero. The management reserve covers unforeseen work (the unknown risks); it is usually a percentage of the budget agreed with the sponsor.'
		=> 'El presupuesto tiene una reserva para contingencias para los riesgos conocidos, pero su reserva de gestión es cero. La reserva de gestión cubre el trabajo imprevisto (los riesgos desconocidos); suele ser un porcentaje del presupuesto acordado con el patrocinador.',
	'Some people have no hourly cost.' => 'Algunas personas no tienen coste por hora.',
	'Some users of the project\'s company have no hourly cost, so the work they do is not counted in the project\'s costs. Enter their cost on the Cost tab of each user.'
		=> 'Algunos usuarios de la empresa del proyecto no tienen coste por hora, así que su trabajo no se cuenta en los costes del proyecto. Introduzca su coste en la pestaña Coste de cada usuario.',
	'Nobody is allocated to the activities.' => 'No hay nadie asignado a las actividades.',
	'The project has activities, but no one is allocated to any of them. Without allocations there are no human resource costs and no workload to check: allocate people to the roles estimated for each activity.'
		=> 'El proyecto tiene actividades, pero nadie está asignado a ninguna. Sin asignaciones no hay costes de recursos humanos ni carga de trabajo que comprobar: asigne personas a los roles estimados para cada actividad.',
	'Someone is allocated to activities that overlap.' => 'Alguien está asignado a actividades que se solapan.',
	'At least one person is allocated to two activities that take place at the same time. Unless both are part-time work, that person is over-allocated: move one of the activities, or allocate someone else.'
		=> 'Al menos una persona está asignada a dos actividades que se realizan a la vez. Salvo que ambas sean a tiempo parcial, esa persona está sobreasignada: mueva una de las actividades o asigne a otra persona.',
	'An eliminated risk has a contingency plan.' => 'Un riesgo eliminado tiene plan de contingencia.',
	'A risk with the Eliminate strategy has a contingency plan. Eliminating a risk means changing the plan so that the risk can no longer happen, so there is nothing to react to. If the risk can still happen, its strategy is Mitigate or Accept.'
		=> 'Un riesgo con la estrategia Eliminar tiene un plan de contingencia. Eliminar un riesgo significa cambiar el plan para que ya no pueda ocurrir, así que no hay nada a lo que reaccionar. Si el riesgo todavía puede ocurrir, su estrategia es Mitigar o Aceptar.',
	'An accepted risk has prevention actions.' => 'Un riesgo aceptado tiene acciones de prevención.',
	'A risk with the Accept strategy has prevention actions. Accepting a risk means not acting unless it happens (at most with a contingency plan). If you plan to act in advance to make it less likely or less serious, its strategy is Mitigate.'
		=> 'Un riesgo con la estrategia Aceptar tiene acciones de prevención. Aceptar un riesgo significa no actuar salvo que ocurra (como mucho, con un plan de contingencia). Si piensa actuar de antemano para que sea menos probable o menos grave, su estrategia es Mitigar.',
	'Low-priority risks have response plans.' => 'Hay riesgos de prioridad baja con planes de respuesta.',
	'Some low-priority risks have prevention actions or a contingency plan. Responses take effort; low-priority risks are usually accepted and monitored, so the effort is better spent on the high-priority ones.'
		=> 'Algunos riesgos de prioridad baja tienen acciones de prevención o un plan de contingencia. Las respuestas cuestan esfuerzo; los riesgos de prioridad baja suelen aceptarse y vigilarse, así que es mejor dedicar el esfuerzo a los de prioridad alta.',
);
