import React from 'react'

/*
  ProfessorDashboard component (React + Tailwind)

  UX decisions (brief):
  - Contrast: dark text on light panels (#111827 on #ffffff / #f3f4f6) ensures >= 4.5:1 for body and table content.
  - Spacing: consistent spacing using an 8px baseline (Tailwind utility multiples such as p-4 = 16px, p-6 = 24px).
  - Inputs: clear focus ring and border color change to a green tone for discoverability and accessibility.
  - Buttons: primary actions (Agregar / Completar) use solid green; secondary/less important actions use ghost buttons
    so they don't visually compete. Buttons inside table are small and grouped to reduce cognitive load.
  - Layout: centered container with max-width avoids edge collision; responsive grid stacks on small screens.
*/

const sampleDisponibilidades = [
  {id:1, fecha:'2026-03-05', inicio:'09:00', fin:'10:00'},
  {id:2, fecha:'2026-03-07', inicio:'14:00', fin:'15:00'},
]

const sampleTutorias = [
  {id:101, estudiante:'María López', materia:'Matemáticas', fecha:'2026-03-05', hora:'09:30', estado:'pendiente'},
  {id:102, estudiante:'Juan Pérez', materia:'Física', fecha:'2026-03-07', hora:'14:30', estado:'aceptada'},
]

export default function ProfessorDashboard(){
  return (
    <div className="space-y-6">
      <header className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Panel Profesor</h1>
          <p className="text-sm text-gray-600 mt-1">Gestiona disponibilidad y solicitudes de tutoría</p>
        </div>
        <div className="flex items-center gap-3">
          <button className="px-4 py-2 rounded-md bg-green-600 text-white text-sm hover:bg-green-700 focus:outline-none">Nuevo</button>
        </div>
      </header>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-medium">Mi Disponibilidad</h2>
          <p className="text-sm text-gray-600 mt-1 mb-4">Registra horarios para recibir solicitudes.</p>

          <form className="space-y-4 mb-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label className="block text-sm font-medium text-gray-700">Fecha</label>
                <input type="date" className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-green-600" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Hora inicio</label>
                <input type="time" className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-green-600" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Hora fin</label>
                <input type="time" className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-green-600" />
              </div>
            </div>
            <div className="flex items-center gap-3">
              <button className="btn-primary px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700">Agregar Disponibilidad</button>
              <button type="button" className="btn-ghost px-4 py-2 rounded-md">Limpiar</button>
            </div>
          </form>

          <h3 className="text-sm font-medium text-gray-700 mb-2">Disponibilidades Próximas</h3>
          <div className="overflow-x-auto">
            <table className="w-full text-sm" aria-label="Disponibilidades">
              <thead className="table-bg text-left text-xs font-semibold text-gray-700">
                <tr>
                  <th className="px-3 py-2">Fecha</th>
                  <th className="px-3 py-2">Inicio</th>
                  <th className="px-3 py-2">Fin</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {sampleDisponibilidades.map(d => (
                  <tr key={d.id}>
                    <td className="px-3 py-2">{d.fecha}</td>
                    <td className="px-3 py-2">{d.inicio}</td>
                    <td className="px-3 py-2">{d.fin}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>

        <section className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-medium">Solicitudes de Tutoría</h2>
          <p className="text-sm text-gray-600 mt-1 mb-4">Acepta, rechaza o marca como completada.</p>

          <div className="overflow-x-auto">
            <table className="w-full text-sm" aria-label="Solicitudes">
              <thead className="table-bg text-left text-xs font-semibold text-gray-700">
                <tr>
                  <th className="px-3 py-2">Estudiante</th>
                  <th className="px-3 py-2">Materia</th>
                  <th className="px-3 py-2">Fecha</th>
                  <th className="px-3 py-2">Hora</th>
                  <th className="px-3 py-2">Estado</th>
                  <th className="px-3 py-2">Acción</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {sampleTutorias.map(t => (
                  <tr key={t.id} className="align-middle">
                    <td className="px-3 py-2">{t.estudiante}</td>
                    <td className="px-3 py-2">{t.materia}</td>
                    <td className="px-3 py-2">{t.fecha}</td>
                    <td className="px-3 py-2">{t.hora}</td>
                    <td className="px-3 py-2">
                      <span className={`inline-block px-2 py-1 text-xs rounded-md ${t.estado==='pendiente'?'bg-yellow-100 text-yellow-800':'bg-green-100 text-green-800'}`}>
                        {t.estado}
                      </span>
                    </td>
                    <td className="px-3 py-2">
                      <div className="flex gap-2">
                        {t.estado==='pendiente' && (
                          <>
                            <button className="px-3 py-1 text-sm rounded-md bg-green-600 text-white hover:bg-green-700">Aceptar</button>
                            <button className="px-3 py-1 text-sm rounded-md btn-ghost">Rechazar</button>
                          </>
                        )}
                        {t.estado==='aceptada' && (
                          <button className="px-3 py-1 text-sm rounded-md bg-green-600 text-white">Completar</button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>
  )
}
