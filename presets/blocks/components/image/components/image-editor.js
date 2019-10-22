export const ImageEditor = (props) => {
  const {
    blockClass,
    url,
  } = props;

  const componentClass = 'image';

  const imageClass = `
    ${componentClass}
    ${blockClass}__img
  `;

  return (
    <img className={imageClass} src={url} alt="" />
  );
};
